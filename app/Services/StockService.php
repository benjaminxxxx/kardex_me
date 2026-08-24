<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Registra un movimiento real (entrada o salida) Y actualiza
     * el saldo materializado en el mismo paso, atómicamente.
     */
    public function registerMovement(
        string $direction,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $movementDate,
        ?string $sourceType = null,
        ?int $sourceId = null,
        array $extra = []
    ): StockMovement {
        return DB::transaction(function () use ($direction, $productId, $warehouseId, $quantity, $movementDate, $sourceType, $sourceId, $extra) {

            // Validación preventiva en salidas manuales
            if ($direction === 'out') {
                $disponible = self::available($productId, $warehouseId);
                if ($quantity > $disponible) {
                    throw new \RuntimeException("Stock insuficiente. Disponible: {$disponible}, solicitado: {$quantity}.");
                }
            }

            $movement = StockMovement::create(array_merge([
                'direction' => $direction,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'movement_date' => $movementDate,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ], $extra));

            $stock = ProductStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0]
            );

            $delta = $direction === 'in' ? $quantity : -$quantity;
            $stock->increment('quantity', $delta);

            return $movement;
        });
    }
    /*public function registerMovement(
        string $direction, // 'in' | 'out'
        int $productId,
        int $warehouseId,
        float $quantity,
        string $movementDate,
        string $sourceType,
        int $sourceId,
        array $extra = []
    ): StockMovement {
        return DB::transaction(function () use ($direction, $productId, $warehouseId, $quantity, $movementDate, $sourceType, $sourceId, $extra) {

            $movement = StockMovement::create(array_merge([
                'direction' => $direction,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'movement_date' => $movementDate,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ], $extra));

            $stock = ProductStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0]
            );

            $delta = $direction === 'in' ? $quantity : -$quantity;
            $stock->increment('quantity', $delta);

            return $movement;
        });
    }*/

    /**
     * Traslado entre almacenes: exactamente tu ejemplo de "sacar de oficina,
     * aumentar en campo". Son 2 movimientos (out + in), un registro de
     * transferencia, y ambos saldos se actualizan en la misma transacción.
     */
    public function transfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        string $transferDate
    ): WarehouseTransfer {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $transferDate) {

            $disponible = self::available($productId, $fromWarehouseId);
            if ($quantity > $disponible) {
                throw new \RuntimeException(
                    "Stock insuficiente en el almacén de origen. Disponible: {$disponible}, solicitado: {$quantity}."
                );
            }

            $transfer = WarehouseTransfer::create([
                'product_id' => $productId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'quantity' => $quantity,
                'transfer_date' => $transferDate,
            ]);

            $this->registerMovement('out', $productId, $fromWarehouseId, $quantity, $transferDate, WarehouseTransfer::class, $transfer->id);
            $this->registerMovement('in', $productId, $toWarehouseId, $quantity, $transferDate, WarehouseTransfer::class, $transfer->id);

            return $transfer;
        });
    }

    /** Saldo cacheado — lo que se muestra en pantalla, rápido, sin sumar historial completo */
    public static function available(int $productId, int $warehouseId): float
    {
        return (float) (ProductStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0);
    }

    /**
     * Saldo REAL, recalculado desde stock_movements. Se usa solo para
     * arqueo/auditoría — nunca en pantallas de uso diario, por costo.
     */
    public static function recalculatedFromMovements(int $productId, int $warehouseId): float
    {
        $entradas = (float) StockMovement::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('direction', 'in')
            ->sum('quantity');

        $salidas = (float) StockMovement::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('direction', 'out')
            ->sum('quantity');

        return $entradas - $salidas;
    }

    /**
     * Arqueo: compara el saldo cacheado contra el recalculado.
     * Si no coincide, hay una inconsistencia que investigar
     * (edición manual en BD, bug, migración de datos incompleta, etc.)
     */
    public static function audit(int $productId, int $warehouseId): array
    {
        $cacheado = self::available($productId, $warehouseId);
        $real = self::recalculatedFromMovements($productId, $warehouseId);

        return [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'cached' => $cacheado,
            'calculated' => $real,
            'matches' => abs($cacheado - $real) < 0.0001, // tolerancia por decimales flotantes
            'difference' => $cacheado - $real,
        ];
    }

    public function reverseMovementsForSource(
        string $sourceType,
        int $sourceId,
        ?int $warehouseId = null,
        ?int $productId = null
    ): void {
        DB::transaction(function () use ($sourceType, $sourceId, $warehouseId, $productId) {
            $movimientos = StockMovement::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->when($productId, fn($q) => $q->where('product_id', $productId))
                ->get();

            foreach ($movimientos as $mov) {
                // Verificar si el movimiento ya fue procesado en el Kárdex
                $tieneKardex = DB::table('kardex_movements')
                    ->where('stock_movement_id', $mov->id)
                    ->exists();

                if ($tieneKardex) {
                    throw new \RuntimeException(
                        "El movimiento de stock #{$mov->id} ya está procesado en el Kárdex para este periodo. " .
                        "No se puede modificar la distribución. Debe eliminar primero el Kárdex generado y volver a intentar."
                    );
                }

                $stock = ProductStock::firstOrCreate(
                    ['product_id' => $mov->product_id, 'warehouse_id' => $mov->warehouse_id],
                    ['quantity' => 0]
                );

                $delta = $mov->direction === 'in' ? -$mov->quantity : $mov->quantity;
                $stock->increment('quantity', $delta);

                $mov->delete();
            }
        });
    }
}