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
        string $direction, // 'in' | 'out'
        int $productId,
        int $warehouseId,
        float $quantity,
        string $movementDate,
        string $sourceType,
        int $sourceId
    ): StockMovement {
        return DB::transaction(function () use ($direction, $productId, $warehouseId, $quantity, $movementDate, $sourceType, $sourceId) {

            $movement = StockMovement::create([
                'direction' => $direction,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'movement_date' => $movementDate,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            $stock = ProductStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0]
            );

            $delta = $direction === 'in' ? $quantity : -$quantity;
            $stock->increment('quantity', $delta);

            return $movement;
        });
    }

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
    /**
     * Elimina todos los movimientos generados por un origen (ej. una Purchase)
     * y revierte su efecto en product_stocks antes de borrarlos.
     * Se usa al editar un documento que ya generó movimientos, para
     * "deshacer" limpiamente antes de volver a registrar los nuevos valores.
     */
    public function reverseMovementsForSource(string $sourceType, int $sourceId): void
    {
        DB::transaction(function () use ($sourceType, $sourceId) {
            $movimientos = StockMovement::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->get();

            foreach ($movimientos as $mov) {
                $stock = ProductStock::firstOrCreate(
                    ['product_id' => $mov->product_id, 'warehouse_id' => $mov->warehouse_id],
                    ['quantity' => 0]
                );

                // Revertir: si fue 'in', restamos; si fue 'out', sumamos de vuelta
                $delta = $mov->direction === 'in' ? -$mov->quantity : $mov->quantity;
                $stock->increment('quantity', $delta);

                $mov->delete();
            }
        });
    }
}