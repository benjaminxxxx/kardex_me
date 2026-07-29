<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\ExplosiveBufferMovement;
use App\Models\ExplosiveFieldDispatch;
use App\Models\ExplosiveFieldDistribution;
use App\Models\ExplosiveRole;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ExplosiveFieldDispatchService
{
    /**
     * Relación columna -> código de rol de producto.
     * Aquí vive el único lugar que sabe "quién es fulminante, quién es emulnor...".
     */
    private array $columnToRole = [
        'fulminante_qty' => 'detonator',
        'emulnor_qty' => 'charge',
        'mecha_lenta_qty' => 'safety_fuse',
        'guia_qty' => 'guide',
        'guia_aux_qty' => 'aux_guide',
        'anfo_qty' => 'bulk_explosive',
    ];
    public function saveDistribution(ExplosiveFieldDispatch $dispatch, array $rows): void
    {

        DB::transaction(function () use ($dispatch, $rows) {

            // 1. Reemplazamos todas las filas de distribución de este despacho
            $dispatch->distributions()->delete();

            foreach ($rows as $row) {
                if (!$row['labor_id'] || !$row['driller_id'])
                    continue;

                $dispatch->distributions()->create([
                    'mining_labor_id' => $row['labor_id'],
                    'driller_employee_id' => $row['driller_id'],
                    'drill_depth_feet' => $row['drill_depth_feet'] ?: null,
                    'guide_length_feet' => $row['guide_length_feet'] ?: 5,
                    'fulminante_qty' => (float) ($row['fulminante_qty'] ?: 0),
                    'emulnor_qty' => (float) ($row['emulnor_qty'] ?: 0),
                    'mecha_lenta_qty' => (float) ($row['mecha_lenta_qty'] ?: 0),
                    'guia_qty' => (float) ($row['guia_qty'] ?: 0),
                    'guia_aux_qty' => (float) ($row['guia_aux_qty'] ?: 0),
                    'anfo_qty' => (float) ($row['anfo_qty'] ?: 0),
                ]);
            }

            // 2. Recalculamos remanente por columna, comparando SOLO contra
            //    este despacho (no el histórico global)
            $recepcionId = CompanySetting::current()->reception_warehouse_id;

            if (!$recepcionId) {
                throw new \RuntimeException('No hay almacén de Recepción configurado.');
            }

            $stockService = app(StockService::class);

            foreach ($this->columnToRole as $column => $roleCode) {


                $solicitado = (float) $dispatch->$column;

                $distribuido = (float) $dispatch->distributions()->sum($column);
                $remanente = $solicitado - $distribuido;
                
                $productColumn = $this->roleCodeToProductColumn($roleCode);
                $productId = $dispatch->$productColumn;

                if (!$productId)
                    continue; // rol sin producto asignado en este despacho

                // 3. Borramos el movimiento de Recepción anterior de ESTE despacho
                //    para este producto (nunca tocamos el movimiento "out" original
                //    de almacén principal, que tiene otro warehouse_id)
                StockMovement::where('source_type', ExplosiveFieldDispatch::class)
                    ->where('source_id', $dispatch->id)
                    ->where('warehouse_id', $recepcionId)
                    ->where('product_id', $productId)
                    ->delete();

                if (abs($remanente) < 0.0001) {
                    continue; // nada que registrar, quedó exacto
                }

                if ($remanente > 0) {
                    // Sobró: se acredita a Recepción
                    $stockService->registerMovement(
                        'in',
                        $productId,
                        $recepcionId,
                        $remanente,
                        now()->toDateString(),
                        ExplosiveFieldDispatch::class,
                        $dispatch->id
                    );
                } else {
                    // Se distribuyó de más: se consume de Recepción lo que faltaba
                    $faltante = abs($remanente);
                    $disponibleEnRecepcion = StockService::available($productId, $recepcionId);

                    if ($faltante > $disponibleEnRecepcion) {
                        throw new \RuntimeException(
                            "No hay suficiente stock en Recepción para cubrir el excedente de '{$roleCode}'. "
                            . "Disponible: {$disponibleEnRecepcion}, necesario: {$faltante}."
                        );
                    }

                    $stockService->registerMovement(
                        'out',
                        $productId,
                        $recepcionId,
                        $faltante,
                        now()->toDateString(),
                        ExplosiveFieldDispatch::class,
                        $dispatch->id
                    );
                }
            }

            // 4. Estado: distribuido solo si TODOS los remanentes quedaron en 0
            $todoCuadrado = collect($this->columnToRole)->keys()->every(function ($column) use ($dispatch) {
                $solicitado = (float) $dispatch->$column;
                $distribuido = (float) $dispatch->distributions()->sum($column);
                return abs($solicitado - $distribuido) < 0.0001;
            });

            $dispatch->update(['status' => $todoCuadrado ? 'distributed' : 'pending_distribution']);
        });
    }
    private function roleCodeToProductColumn(string $roleCode): string
    {
        // detonator -> fulminante_product_id, charge -> emulnor_product_id, etc.
        $map = [
            'detonator' => 'fulminante_product_id',
            'charge' => 'emulnor_product_id',
            'safety_fuse' => 'mecha_lenta_product_id',
            'guide' => 'guia_product_id',
            'aux_guide' => 'guia_aux_product_id',
            'bulk_explosive' => 'anfo_product_id',
        ];

        return $map[$roleCode];
    }
   
public function create(array $header, array $quantitiesByRoleCode, array $selectedProductsByRoleCode): ExplosiveFieldDispatch
{
    return DB::transaction(function () use ($header, $quantitiesByRoleCode, $selectedProductsByRoleCode) {

        $roleToColumn = array_flip($this->columnToRole);
        $columnValues = [];

        foreach ($quantitiesByRoleCode as $roleCode => $qty) {
            if (! isset($roleToColumn[$roleCode])) continue;

            $column = $roleToColumn[$roleCode];
            $columnValues[$column] = filled($qty) ? (float) $qty : 0;

            // Esto es lo que faltaba: guardar también el producto elegido
            $productColumn = $this->roleCodeToProductColumn($roleCode);
            $columnValues[$productColumn] = $selectedProductsByRoleCode[$roleCode] ?? null;
        }

        $dispatch = ExplosiveFieldDispatch::create(array_merge($header, $columnValues));

        $stockService = app(StockService::class);

        foreach ($this->columnToRole as $column => $roleCode) {
            $solicitado = (float) ($columnValues[$column] ?? 0);
            if ($solicitado <= 0) continue;

            $productId = $selectedProductsByRoleCode[$roleCode] ?? null;
            if (! $productId) {
                throw new \RuntimeException("No se seleccionó producto para el rol '{$roleCode}'.");
            }

            $disponible = StockService::available($productId, $header['warehouse_id']);
            if ($solicitado > $disponible) {
                throw new \RuntimeException(
                    "Stock insuficiente de '{$roleCode}' al confirmar. Disponible: {$disponible}, solicitado: {$solicitado}."
                );
            }

            $stockService->registerMovement(
                'out',
                $productId,
                $header['warehouse_id'],
                $solicitado,
                $dispatch->dispatch_date,
                ExplosiveFieldDispatch::class,
                $dispatch->id
            );
        }

        return $dispatch;
    });
}
    /**
     * Sobrante acumulado histórico de una columna = todo lo despachado
     * menos todo lo distribuido, hasta el momento. Si el resultado es
     * negativo (no debería pasar, pero por seguridad), se trata como 0.
     */
    private function calcularSobranteAcumulado(string $column): float
    {
        $totalDespachado = (float) ExplosiveFieldDispatch::sum($column);
        $totalDistribuido = (float) ExplosiveFieldDistribution::sum($column);

        return max($totalDespachado - $totalDistribuido, 0);
    }
}