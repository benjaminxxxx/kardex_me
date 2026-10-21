<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Kardex;
use App\Models\ProductStock;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private StockService $stockService)
    {
    }

    public function create(array $header, array $items): Purchase
    {
        return DB::transaction(function () use ($header, $items) {
            $totals = $this->calculateTotals($items);

            $purchase = Purchase::create(array_merge($header, $totals));

            $this->createItemsAndStock($purchase, $items);

            return $purchase;
        });
    }

    /**
     * Elimina (soft delete) una compra con motivo obligatorio y REVIERTE el stock
     * que generó. Si sus movimientos están en un kárdex abierto, se quitan de él y
     * se recalcula; si están en un kárdex cerrado, no se permite eliminar.
     */
    public function delete(Purchase $purchase, string $reason): void
    {
        DB::transaction(function () use ($purchase, $reason) {
            $purchase->load(['items', 'supplier.person', 'warehouse']);
            $snapshot = $this->snapshot($purchase);
            $movements = $purchase->stockMovements()->with('product')->get();

            $kardexLines = DB::table('kardex_movements as km')
                ->join('kardexes as k', 'k.id', '=', 'km.kardex_id')
                ->whereIn('km.stock_movement_id', $movements->pluck('id'))
                ->get(['km.id', 'k.id as kardex_id', 'k.status', 'k.month', 'k.year']);

            if ($closed = $kardexLines->firstWhere('status', 'closed')) {
                throw new \RuntimeException(
                    "No se puede eliminar: la compra está incluida en el kárdex cerrado de {$closed->month}/{$closed->year}."
                );
            }

            foreach ($movements as $mov) {
                $saldo = (float) ProductStock::where('product_id', $mov->product_id)
                    ->where('warehouse_id', $mov->warehouse_id)
                    ->lockForUpdate()
                    ->value('quantity');

                if ($saldo - (float) $mov->quantity < -0.0001) {
                    throw new \RuntimeException(
                        "No se puede eliminar: el stock de {$mov->product?->name} quedaría en " .
                        ($saldo - (float) $mov->quantity) . '. Parte de esta compra ya fue despachada.'
                    );
                }
            }

            // Quitar las líneas del kárdex abierto para poder revertir los movimientos
            DB::table('kardex_movements')->whereIn('id', $kardexLines->pluck('id'))->delete();

            $this->stockService->reverseMovementsForSource(Purchase::class, $purchase->id);

            $purchase->deletion_reason = $reason;
            $purchase->save();
            $purchase->delete(); // HasAuditColumns guarda deleted_by / deleted_by_name

            foreach ($kardexLines->pluck('kardex_id')->unique() as $kardexId) {
                app(KardexService::class)->calculate(Kardex::findOrFail($kardexId));
            }

            AuditLogger::log(
                'purchase.deleted',
                "Compra {$purchase->document_type} {$purchase->document_series}-{$purchase->document_number} eliminada; se revirtieron {$movements->count()} movimiento(s) de stock",
                $purchase,
                old: $snapshot,
                reason: $reason,
                severity: AuditLog::SEVERITY_CRITICAL,
            );
        });
    }

    /**
     * Restaura una compra eliminada y vuelve a registrar su stock.
     * Las compras eliminadas antes de este cambio conservan su stock,
     * así que en ese caso no se duplica.
     */
    public function restore(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $purchase->load('items');
            $hadStock = $purchase->stockMovements()->exists();
            $date = \Carbon\Carbon::parse($purchase->document_date);

            $kardexes = Kardex::whereIn('product_id', $purchase->items->pluck('product_id'))
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->get();

            if (!$hadStock && $closed = $kardexes->firstWhere('status', 'closed')) {
                throw new \RuntimeException(
                    "No se puede restaurar: el kárdex de {$closed->month}/{$closed->year} ya está cerrado."
                );
            }

            $reason = $purchase->deletion_reason;
            $purchase->restore();
            $purchase->deletion_reason = null;
            $purchase->save();

            if (!$hadStock) {
                foreach ($purchase->items as $item) {
                    $this->stockService->registerMovement(
                        'in',
                        $item->product_id,
                        $purchase->warehouse_id,
                        (float) $item->quantity_base,
                        $date->toDateString(),
                        Purchase::class,
                        $purchase->id,
                        ['purchase_item_id' => $item->id]
                    );
                }

                foreach ($kardexes as $kardex) {
                    app(KardexService::class)->calculate($kardex);
                }
            }

            AuditLogger::log(
                'purchase.restored',
                "Compra {$purchase->document_series}-{$purchase->document_number} restaurada" .
                ($hadStock ? ' (su stock nunca se había revertido)' : '; se volvió a registrar su stock'),
                $purchase,
                old: ['deletion_reason' => $reason],
                severity: AuditLog::SEVERITY_WARNING,
            );
        });
    }

    private function snapshot(Purchase $purchase): array
    {
        return [
            'proveedor' => $purchase->supplier?->person?->display_name,
            'almacen' => $purchase->warehouse?->name,
            'comprobante' => trim("{$purchase->document_type} {$purchase->document_series}-{$purchase->document_number}"),
            'fecha' => $purchase->document_date?->toDateString(),
            'moneda' => $purchase->currency,
            'total' => (float) $purchase->total,
            'items' => $purchase->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'cantidad_base' => (float) $i->quantity_base,
                'costo_unitario' => (float) $i->unit_cost,
                'total_linea' => (float) $i->line_total,
            ])->all(),
        ];
    }

    public function update(Purchase $purchase, array $header, array $items): Purchase
    {
        return DB::transaction(function () use ($purchase, $header, $items) {
            $purchase->load(['items', 'supplier.person', 'warehouse']);
            $before = $this->snapshot($purchase);

            // 1. Revertir stock generado por la versión anterior de esta compra
            $this->stockService->reverseMovementsForSource(Purchase::class, $purchase->id);

            // 2. Borrar items anteriores
            $purchase->items()->delete();

            // 3. Recalcular y actualizar cabecera
            $totals = $this->calculateTotals($items);
            $purchase->update(array_merge($header, $totals));

            // 4. Recrear items y stock con los nuevos valores
            $this->createItemsAndStock($purchase, $items);

            $purchase->refresh()->load(['items', 'supplier.person', 'warehouse']);
            [$old, $new] = AuditLogger::diff($before, $this->snapshot($purchase));

            if ($old || $new) {
                AuditLogger::log(
                    'purchase.updated',
                    "Compra {$purchase->document_series}-{$purchase->document_number} editada",
                    $purchase,
                    old: $old,
                    new: $new,
                    severity: AuditLog::SEVERITY_WARNING,
                );
            }

            return $purchase;
        });
    }

    private function createItemsAndStock(Purchase $purchase, array $items): void
    {
        foreach ($items as $item) {
            $quantityBase = (float) $item['quantity'] * (float) ($item['conversion_factor'] ?? 1);

            // Costo de inventario: precio neto (con descuento aplicado),
            // EXCLUYENDO IGV — el IGV es crédito fiscal recuperable, no forma
            // parte del costo del bien (criterio NIC 2). Confirmar con contador
            // si la empresa NO tiene derecho a crédito fiscal por algún motivo;
            // en ese caso este cálculo debería incluir el IGV.
            $baseAmount = (float) $item['quantity'] * (float) $item['unit_cost'];
            $discountAmount = $baseAmount * ((float) ($item['discount_percent'] ?? 0) / 100);
            $netCost = $baseAmount - $discountAmount;
            $unitCostBase = $quantityBase > 0 ? round($netCost / $quantityBase, 6) : 0;

            $purchaseItem = $purchase->items()->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'] ?: null,
                'quantity' => $item['quantity'],
                'quantity_base' => $quantityBase,
                'unit_cost' => $item['unit_cost'],
                'unit_cost_base' => $unitCostBase,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'igv_percent' => $item['igv_percent'] ?? 18,
                'line_total' => $this->lineTotal($item),
            ]);

            $this->stockService->registerMovement(
                'in',
                $item['product_id'],
                $purchase->warehouse_id,
                $quantityBase,
                $purchase->document_date,
                Purchase::class,
                $purchase->id,
                ['purchase_item_id' => $purchaseItem->id]
            );
        }
    }

    private function lineTotal(array $item): float
    {
        $base = (float) $item['quantity'] * (float) $item['unit_cost'];
        $descuento = $base * ((float) ($item['discount_percent'] ?? 0) / 100);
        $neto = $base - $descuento;
        $igv = $neto * ((float) ($item['igv_percent'] ?? 18) / 100);

        return round($neto + $igv, 4);
    }

    private function calculateTotals(array $items): array
    {
        $subtotalNeto = 0;
        $igvTotal = 0;

        foreach ($items as $item) {
            $base = (float) $item['quantity'] * (float) $item['unit_cost'];
            $descuento = $base * ((float) ($item['discount_percent'] ?? 0) / 100);
            $neto = $base - $descuento;
            $igv = $neto * ((float) ($item['igv_percent'] ?? 18) / 100);

            $subtotalNeto += $neto;
            $igvTotal += $igv;
        }

        return [
            'subtotal_neto' => round($subtotalNeto, 4),
            'igv_total' => round($igvTotal, 4),
            'total' => round($subtotalNeto + $igvTotal, 4),
        ];
    }
}