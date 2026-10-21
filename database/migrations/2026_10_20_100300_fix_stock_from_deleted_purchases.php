<?php

use App\Models\AuditLog;
use App\Models\Kardex;
use App\Models\Purchase;
use App\Services\AuditLogger;
use App\Services\KardexService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrección de datos: antes, eliminar una compra NO revertía el stock que
 * había ingresado. Esta migración revierte ese stock y recalcula los kárdex
 * abiertos afectados.
 *
 * Por producto/almacén:
 *  - Si el saldo queda >= 0, se corrige.
 *  - Si quedaría negativo (ya se despachó ese stock) o la compra está en un
 *    kárdex cerrado, NO se toca y se registra "stock.review_required" en
 *    Auditoría para revisión manual.
 *
 * Todo queda en audit_logs con la foto de cada movimiento, y down() los repone.
 */
return new class extends Migration
{
    private const TAG = '2026_10_20_100300_fix_stock_from_deleted_purchases';

    public function up(): void
    {
        DB::transaction(function () {
            $deleted = DB::table('purchases')->whereNotNull('deleted_at')
                ->get(['id', 'document_series', 'document_number', 'deleted_at', 'deleted_by_name'])
                ->keyBy('id');

            $movements = DB::table('stock_movements')
                ->where('source_type', Purchase::class)
                ->whereIn('source_id', $deleted->keys())
                ->orderBy('id')
                ->get();

            $kardexToRecalc = [];

            foreach ($movements->groupBy(fn($m) => "{$m->product_id}-{$m->warehouse_id}") as $group) {
                $first = $group->first();
                $total = (float) $group->sum('quantity');
                $current = (float) DB::table('product_stocks')
                    ->where('product_id', $first->product_id)
                    ->where('warehouse_id', $first->warehouse_id)
                    ->lockForUpdate()
                    ->value('quantity');
                $after = $current - $total;

                $kardexLines = DB::table('kardex_movements as km')
                    ->join('kardexes as k', 'k.id', '=', 'km.kardex_id')
                    ->whereIn('km.stock_movement_id', $group->pluck('id'))
                    ->get(['km.id', 'k.id as kardex_id', 'k.status', 'k.month', 'k.year']);

                $purchases = $group->pluck('source_id')->unique()->map(fn($id) => [
                    'purchase_id' => $id,
                    'comprobante' => trim(($deleted[$id]->document_series ?? '') . '-' . ($deleted[$id]->document_number ?? ''), '-'),
                    'eliminada' => $deleted[$id]->deleted_at,
                    'eliminada_por' => $deleted[$id]->deleted_by_name,
                ])->values()->all();

                $summary = [
                    'migration' => self::TAG,
                    'product_id' => $first->product_id,
                    'warehouse_id' => $first->warehouse_id,
                    'stock_actual' => $current,
                    'cantidad_a_revertir' => $total,
                    'stock_resultante' => $after,
                    'compras' => $purchases,
                ];

                $closed = $kardexLines->firstWhere('status', 'closed');

                if ($after < -0.0001 || $closed) {
                    AuditLogger::log(
                        'stock.review_required',
                        "Producto #{$first->product_id}: stock de compras eliminadas NO revertido, requiere revisión",
                        old: $summary,
                        reason: $closed
                            ? "Incluido en el kárdex cerrado {$closed->month}/{$closed->year}."
                            : "El stock quedaría en {$after}: ya se despachó. Restaurar las compras si eran válidas, o registrar un ajuste.",
                        severity: AuditLog::SEVERITY_CRITICAL,
                    );
                    continue;
                }

                DB::table('kardex_movements')->whereIn('id', $kardexLines->pluck('id'))->delete();
                DB::table('stock_movements')->whereIn('id', $group->pluck('id'))->delete();
                DB::table('product_stocks')
                    ->where('product_id', $first->product_id)
                    ->where('warehouse_id', $first->warehouse_id)
                    ->update(['quantity' => DB::raw('quantity - ' . $total), 'updated_at' => now()]);

                foreach ($kardexLines->pluck('kardex_id')->unique() as $kardexId) {
                    $kardexToRecalc[$kardexId] = true;
                }

                AuditLogger::log(
                    'stock.data_fix',
                    "Producto #{$first->product_id}: revertidas {$total} u. de compras eliminadas (stock {$current} → {$after})",
                    old: ['movimientos' => $group->map(fn($m) => (array) $m)->values()->all()],
                    new: $summary,
                    reason: 'Corrección: eliminar una compra no revertía su stock.',
                    severity: AuditLog::SEVERITY_CRITICAL,
                );
            }

            foreach (array_keys($kardexToRecalc) as $kardexId) {
                app(KardexService::class)->calculate(Kardex::findOrFail($kardexId));
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $logs = AuditLog::where('event', 'stock.data_fix')
                ->where('new_values->migration', self::TAG)
                ->get();

            $periods = [];

            foreach ($logs as $log) {
                foreach ($log->old_values['movimientos'] ?? [] as $mov) {
                    if (DB::table('stock_movements')->where('id', $mov['id'])->exists()) {
                        continue;
                    }

                    DB::table('stock_movements')->insert($mov);
                    DB::table('product_stocks')
                        ->where('product_id', $mov['product_id'])
                        ->where('warehouse_id', $mov['warehouse_id'])
                        ->update(['quantity' => DB::raw('quantity + ' . (float) $mov['quantity'])]);

                    $date = \Carbon\Carbon::parse($mov['movement_date']);
                    $periods["{$mov['product_id']}-{$date->year}-{$date->month}"] = [$mov['product_id'], $date->year, $date->month];
                }
            }

            foreach ($periods as [$productId, $year, $month]) {
                $kardex = Kardex::where(compact('year', 'month') + ['product_id' => $productId])->first();
                if ($kardex && $kardex->status === 'open') {
                    app(KardexService::class)->calculate($kardex);
                }
            }

            if ($logs->isNotEmpty()) {
                AuditLogger::log(
                    'stock.data_fix',
                    'Se deshizo la corrección de stock de compras eliminadas (rollback de migración)',
                    new: ['rollback_of' => self::TAG],
                    severity: AuditLog::SEVERITY_CRITICAL,
                );
            }
        });
    }
};
