<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\StockMovement;
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

    public function update(Purchase $purchase, array $header, array $items): Purchase
    {
        return DB::transaction(function () use ($purchase, $header, $items) {

            // 1. Revertir stock generado por la versión anterior de esta compra
            $this->stockService->reverseMovementsForSource(Purchase::class, $purchase->id);

            // 2. Borrar items anteriores
            $purchase->items()->delete();

            // 3. Recalcular y actualizar cabecera
            $totals = $this->calculateTotals($items);
            $purchase->update(array_merge($header, $totals));

            // 4. Recrear items y stock con los nuevos valores
            $this->createItemsAndStock($purchase, $items);

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