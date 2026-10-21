<?php

namespace App\Livewire;

use App\Constants\Permisos;
use App\Models\AuditLog;
use App\Models\ExplosiveFieldDispatch;
use App\Models\Kardex;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Panel de control')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $monthStart = now()->startOfMonth();

        $data = ['alerts' => []];

        // ===== Despachos de explosivos =====
        if ($user->can(Permisos::DESPACHOS_EXPLOSIVOS_VER)) {
            $data['pendingDistribution'] = ExplosiveFieldDispatch::where('status', ExplosiveFieldDispatch::STATUS_PENDING_DISTRIBUTION)->count();
            $data['dispatchesMonth'] = ExplosiveFieldDispatch::where('dispatch_date', '>=', $monthStart)->count();
            $data['lastDispatchDate'] = ExplosiveFieldDispatch::max('dispatch_date');
        }

        // ===== Compras del mes =====
        if ($user->can(Permisos::COMPRAS_VER)) {
            $data['purchasesMonth'] = Purchase::where('document_date', '>=', $monthStart)
                ->selectRaw('currency, count(*) as n, sum(total) as total')
                ->groupBy('currency')
                ->get();
            $data['deletedPurchasesMonth'] = Purchase::onlyTrashed()->where('deleted_at', '>=', $monthStart)->count();
        }

        // ===== Stock actual =====
        if ($user->can(Permisos::PRODUCTOS_VER) || $user->can(Permisos::MOVIMIENTOS_VER)) {
            $data['stock'] = DB::table('product_stocks as s')
                ->join('products as p', 'p.id', '=', 's.product_id')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
                ->whereNull('p.deleted_at')
                ->where('s.quantity', '!=', 0)
                ->orderBy('w.name')->orderBy('p.name')
                ->get(['p.code', 'p.name', 'w.name as warehouse', 's.quantity', 'u.alias as unit']);

            // Arqueo: saldo guardado vs. suma real de movimientos
            $mismatches = DB::table('product_stocks as s')
                ->leftJoinSub(
                    DB::table('stock_movements')
                        ->selectRaw("product_id, warehouse_id, sum(case when direction = 'in' then quantity else -quantity end) as real_qty")
                        ->groupBy('product_id', 'warehouse_id'),
                    'm',
                    fn($j) => $j->on('m.product_id', '=', 's.product_id')->on('m.warehouse_id', '=', 's.warehouse_id')
                )
                ->whereRaw('abs(s.quantity - coalesce(m.real_qty, 0)) > 0.0001')
                ->count();

            if ($mismatches) {
                $data['alerts'][] = ['danger', "{$mismatches} saldo(s) de stock no coinciden con sus movimientos. Revisar en Movimientos."];
            }

            $negative = DB::table('product_stocks')->where('quantity', '<', 0)->count();
            if ($negative) {
                $data['alerts'][] = ['danger', "{$negative} producto(s) con stock negativo."];
            }
        }

        // ===== Kárdex =====
        if ($user->can(Permisos::KARDEX_VER)) {
            $data['openKardex'] = Kardex::where('status', 'open')->count();

            $staleKardex = Kardex::where('status', 'open')
                ->where(fn($q) => $q->where('year', '<', now()->year)
                    ->orWhere(fn($qq) => $qq->where('year', now()->year)->where('month', '<', now()->month - 1)))
                ->count();

            if ($staleKardex) {
                $data['alerts'][] = ['warning', "{$staleKardex} kárdex de meses anteriores siguen abiertos."];
            }
        }

        // ===== Auditoría =====
        if ($user->can(Permisos::AUDITORIA_VER)) {
            $data['recentAudit'] = AuditLog::whereIn('severity', [AuditLog::SEVERITY_CRITICAL, AuditLog::SEVERITY_WARNING])
                ->latest('id')->take(6)->get();

        }

        // Compras eliminadas que todavía suman stock (casos que la corrección no pudo revertir)
        if ($user->can(Permisos::COMPRAS_VER)) {
            $pending = DB::table('purchases as p')
                ->whereNotNull('p.deleted_at')
                ->whereExists(fn($q) => $q->from('stock_movements as m')
                    ->whereColumn('m.source_id', 'p.id')
                    ->where('m.source_type', Purchase::class))
                ->count();

            if ($pending) {
                $data['alerts'][] = ['danger', "{$pending} compra(s) eliminada(s) siguen sumando stock. Restaurarlas si eran válidas o corregir el stock (detalle en Auditoría)."];
            }
        }

        return view('livewire.dashboard', $data);
    }
}
