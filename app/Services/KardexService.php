<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\ExplosiveFieldDispatch;
use App\Models\Kardex;
use App\Models\KardexMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\CostingYearSetting;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Support\ExcelHelper;
use Exception;

class KardexService
{
    /**
     * Resuelve tipo (código SUNAT Tabla 10), serie y número de comprobante
     * para el snapshot del Kardex. Si el origen no es una Purchase, o si
     * es una Purchase con nota_venta (sin código SUNAT), se guarda null
     * en document_type — indicando que no hay comprobante tributario formal.
     */
    private function resolveDocumentReference(StockMovement $mov): array
    {
        if ($mov->source_type === Purchase::class) {
            $purchase = Purchase::find($mov->source_id);

            if ($purchase) {
                $sunatCode = config('sunat_document_types')[$purchase->document_type] ?? null;

                return [
                    'document_type' => $sunatCode,
                    'document_series' => $purchase->document_series,
                    'document_number' => $purchase->document_number,
                ];
            }
        }

        return ['document_type' => null, 'document_series' => null, 'document_number' => null];
    }

    /**
     * Calcula qué saldo (cantidad + costo promedio) tendría el producto
     * justo ANTES de una fecha de corte, simulando todos los movimientos
     * históricos reales. Se usa solo para sugerir el saldo inicial al abrir
     * el PRIMER kardex de un producto que empieza a mitad de año.
     * No persiste nada — es solo una sugerencia editable por el usuario.
     */
    public function previewOpeningBalanceAsOf(int $productId, \Carbon\Carbon $cutoffExclusive): array
    {
        $movimientos = StockMovement::where('product_id', $productId)
            ->where('movement_date', '<', $cutoffExclusive)
            ->orderBy('movement_date')->orderBy('id')
            ->get();

        $saldoQty = 0.0;
        $saldoCosto = 0.0;

        foreach ($movimientos as $mov) {
            if ($mov->direction === 'in') {
                $costoUnitarioEntrada = $this->resolveEntryUnitCost($mov, $saldoCosto);
                $costoTotalEntrada = (float) $mov->quantity * $costoUnitarioEntrada;

                $nuevaQty = $saldoQty + (float) $mov->quantity;
                $nuevoCostoTotal = ($saldoQty * $saldoCosto) + $costoTotalEntrada;
                $saldoCosto = $nuevaQty > 0 ? round($nuevoCostoTotal / $nuevaQty, 6) : $saldoCosto;
                $saldoQty = $nuevaQty;
            } else {
                $saldoQty -= (float) $mov->quantity;
            }
        }

        return ['qty' => round($saldoQty, 4), 'unit_cost' => $saldoCosto];
    }

    /**
     * Abre el PRIMER kardex de un producto en cualquier mes/año, con saldo
     * inicial explícito (sugerido por previewOpeningBalanceAsOf, pero
     * editable). Solo permitido si el producto no tiene ningún kardex previo.
     */
    public function openFirst(int $productId, int $year, int $month, float $openingQty, float $openingUnitCost): Kardex
    {
        if (Kardex::where('product_id', $productId)->exists()) {
            throw new \RuntimeException('Este producto ya tiene un kardex. Usa la continuación secuencial en vez de abrir uno nuevo.');
        }

        $metodo = 'average';

        return Kardex::create([
            'product_id' => $productId,
            'year' => $year,
            'month' => $month,
            'costing_method' => $metodo,
            'opening_qty' => $openingQty,
            'opening_unit_cost' => $openingUnitCost,
            'opening_total_cost' => round($openingQty * $openingUnitCost, 4),
            'status' => 'open',
        ]);
    }
    /**
     * Crea el kardex del siguiente mes disponible para un producto.
     * Bloquea si el mes anterior no está cerrado (regla de secuencia).
     */
    public function openNext(int $productId, int $year): Kardex
    {
        $ultimoCerrado = Kardex::where('product_id', $productId)
            ->where('year', '<=', $year)
            ->where('status', 'closed')
            ->orderByDesc('year')->orderByDesc('month')
            ->first();

        [$nextYear, $nextMonth] = $ultimoCerrado
            ? $this->nextPeriod($ultimoCerrado->year, $ultimoCerrado->month)
            : [$year, 1]; // primer kardex de la vida del producto

        $yaExiste = Kardex::where('product_id', $productId)
            ->where('year', $nextYear)->where('month', $nextMonth)
            ->exists();

        if ($yaExiste) {
            throw new \RuntimeException("Ya existe un kardex para {$nextMonth}/{$nextYear} de este producto.");
        }

        $metodo = 'average';

        $openingQty = $ultimoCerrado->closing_qty ?? 0;
        $openingUnitCost = $ultimoCerrado->closing_unit_cost ?? 0;

        return Kardex::create([
            'product_id' => $productId,
            'year' => $nextYear,
            'month' => $nextMonth,
            'costing_method' => $metodo,
            'opening_qty' => $openingQty,
            'opening_unit_cost' => $openingUnitCost,
            'opening_total_cost' => round($openingQty * $openingUnitCost, 4),
            'status' => 'open',
        ]);
    }

    private function nextPeriod(int $year, int $month): array
    {
        return $month === 12 ? [$year + 1, 1] : [$year, $month + 1];
    }
    private function resolveOperationType(StockMovement $mov): string
    {
        $settings = CompanySetting::current();

        // Compra
        if ($mov->source_type === Purchase::class) {
            return '02';
        }

        // Despacho de explosivos
        if ($mov->source_type === ExplosiveFieldDispatch::class) {

            // Si el movimiento pasa por el almacén de recepción,
            // tributariamente sigue siendo una transferencia interna.
            if (
                $settings->reception_warehouse_id &&
                $mov->warehouse_id == $settings->reception_warehouse_id
            ) {
                return $mov->direction === 'in'
                    ? '21' // Entrada por transferencia entre almacenes
                    : '11'; // Salida por transferencia entre almacenes
            }

            // Movimiento normal hacia producción
            return $mov->direction === 'out'
                ? '10' // Salida a producción
                : '91'; // No debería existir normalmente
        }

        // Transferencias normales
        if ($mov->source_type === WarehouseTransfer::class) {
            return $mov->direction === 'in'
                ? '21'
                : '11';
        }

        return '91';
    }
    /**
     * Trae los stock_movements del periodo y recalcula todo el kardex
     * (borra líneas anteriores y las reconstruye). Solo permitido si
     * el kardex sigue 'open'.
     */
    public function calculate(Kardex $kardex): void
    {
        if ($kardex->status === 'closed') {
            throw new \RuntimeException('No se puede recalcular un kardex cerrado.');
        }

        DB::transaction(function () use ($kardex) {
            $kardex->movements()->delete();

            $desde = \Carbon\Carbon::create($kardex->year, $kardex->month, 1)->startOfMonth();
            $hasta = $desde->copy()->endOfMonth();

            $movimientos = StockMovement::where('product_id', $kardex->product_id)
                ->whereBetween('movement_date', [$desde, $hasta])
                ->orderBy('movement_date')->orderBy('id')
                ->get();

            $saldoQty = (float) $kardex->opening_qty;
            $saldoCosto = (float) $kardex->opening_unit_cost;

            $totalEntradasQty = 0;
            $totalEntradasCosto = 0;
            $totalSalidasQty = 0;
            $totalSalidasCosto = 0;

            foreach ($movimientos as $mov) {
                $label = $mov->source_label;
                $documentRef = $this->resolveDocumentReference($mov);

                if ($mov->direction === 'in') {
                    // Costo de entrada: viene del costo real de compra si el
                    // origen es una Purchase; si no, se asume el costo promedio vigente
                    $costoUnitarioEntrada = $this->resolveEntryUnitCost($mov, $saldoCosto);

                    $costoTotalEntrada = round((float) $mov->quantity * $costoUnitarioEntrada, 4);

                    // Promedio ponderado: (saldo_actual + entrada) / (qty_actual + qty_entrada)
                    $nuevaQty = $saldoQty + (float) $mov->quantity;
                    $nuevoCostoTotal = ($saldoQty * $saldoCosto) + $costoTotalEntrada;
                    $saldoCosto = $nuevaQty > 0 ? round($nuevoCostoTotal / $nuevaQty, 6) : $saldoCosto;
                    $saldoQty = $nuevaQty;

                    $totalEntradasQty += (float) $mov->quantity;
                    $totalEntradasCosto += $costoTotalEntrada;


                    $operationType = $this->resolveOperationType($mov);

                    KardexMovement::create([
                        'kardex_id' => $kardex->id,
                        'stock_movement_id' => $mov->id,
                        'movement_date' => $mov->movement_date,
                        'direction' => 'in',
                        'source_label' => $label,
                        'document_type' => $documentRef['document_type'],
                        'document_series' => $documentRef['document_series'],
                        'document_number' => $documentRef['document_number'],
                        'operation_type' => $operationType,
                        'entry_qty' => $mov->quantity,
                        'entry_unit_cost' => $costoUnitarioEntrada,
                        'entry_total_cost' => $costoTotalEntrada,
                        'balance_qty' => $saldoQty,
                        'balance_unit_cost' => $saldoCosto,
                        'balance_total_cost' => round($saldoQty * $saldoCosto, 4),
                    ]);
                } else {
                    // Salida: siempre al costo promedio vigente en ese momento
                    $costoTotalSalida = round((float) $mov->quantity * $saldoCosto, 4);

                    $saldoQty -= (float) $mov->quantity;

                    $totalSalidasQty += (float) $mov->quantity;
                    $totalSalidasCosto += $costoTotalSalida;

                    $operationType = $this->resolveOperationType($mov);

                    KardexMovement::create([
                        'kardex_id' => $kardex->id,
                        'stock_movement_id' => $mov->id,
                        'movement_date' => $mov->movement_date,
                        'direction' => 'out',
                        'source_label' => $label,
                        'document_type' => $documentRef['document_type'],
                        'document_series' => $documentRef['document_series'],
                        'document_number' => $documentRef['document_number'],
                        'operation_type' => $operationType,
                        'exit_qty' => $mov->quantity,
                        'exit_unit_cost' => $saldoCosto,
                        'exit_total_cost' => $costoTotalSalida,
                        'balance_qty' => $saldoQty,
                        'balance_unit_cost' => $saldoCosto,
                        'balance_total_cost' => round($saldoQty * $saldoCosto, 4),
                    ]);
                }
            }

            $excelPath = $this->generateExcelPath($kardex);

            $kardex->update([
                'total_entries_qty' => $totalEntradasQty,
                'total_entries_cost' => round($totalEntradasCosto, 4),
                'total_exits_qty' => $totalSalidasQty,
                'total_exits_cost' => round($totalSalidasCosto, 4),
                'closing_qty' => $saldoQty,
                'closing_unit_cost' => $saldoCosto,
                'closing_total_cost' => round($saldoQty * $saldoCosto, 4),
                'excel_path' => $excelPath
            ]);
        });
    }
    private function generateExcelPath($kardex)
    {

        $product = $kardex->product;

        $spreadsheet = ExcelHelper::loadTemplate('sunat_234_formato131.xlsx');
        $hoja = $spreadsheet->getSheetByName('F13');

        if (!$hoja) {
            throw new Exception("La plantilla no contiene la hoja 'F13'.");
        }
        /*
                    // 3. Rellenar Encabezados de Información (Filas 3 a 11)
                    $hoja->setCellValue('A3', 'PERÍODO: ' . $kardex->period());
                    $hoja->setCellValue('A4', 'RUC: ' . (config('company.ruc') ?? ''));
                    $hoja->setCellValue('A5', 'APELLIDOS Y NOMBRES, DENOMINACIÓN O RAZÓN SOCIAL: ' . (config('company.name') ?? ''));
                    $hoja->setCellValue('A6', 'ESTABLECIMIENTO (1): ' . ($product->establishment ?? 'PRINCIPAL'));
                    $hoja->setCellValue('A7', 'CÓDIGO DE LA EXISTENCIA: ' . ($product->code ?? ''));
                    $hoja->setCellValue('A8', 'TIPO (TABLA 5): ' . ($product->sunat_type ?? ''));
                    $hoja->setCellValue('A9', 'DESCRIPCIÓN: ' . ($product->name ?? ''));
                    $hoja->setCellValue('A10', 'CÓDIGO DE LA UNIDAD DE MEDIDA (TABLA 6): ' . ($product->unit_measure_code ?? 'NIU'));
                    $hoja->setCellValue('A11', 'MÉTODO DE VALUACIÓN: ' . strtoupper($kardex->costing_method ?? 'PROMEDIO'));
        */
        // ===== Fila 16: Saldo inicial, SOLO si es distinto de cero =====
        $tieneSaldoInicial = (float) $kardex->opening_qty > 0;
        $filaInicio = 17;

        if ($tieneSaldoInicial) {
            // 1. Primer día del mes correspondiente al Kardex (ejemplo: 01/05/2026)
            $fechaInicioMes = sprintf('01/%02d/%04d', $kardex->month, $kardex->year);

            // 2. Columna de Documentos (A-D): Tipo de operación 16 (Saldo Inicial)
            $hoja->setCellValue("A{$filaInicio}", $fechaInicioMes);
            $hoja->setCellValue("B{$filaInicio}", '');
            $hoja->setCellValue("C{$filaInicio}", '');
            $hoja->setCellValue("D{$filaInicio}", '');
            $hoja->setCellValue("E{$filaInicio}", '16');

            // 3. Entradas (F, G, H): Se registra la Apertura como Entrada de Inventario
            $hoja->setCellValue("F{$filaInicio}", (float) $kardex->opening_qty);
            $hoja->setCellValue("G{$filaInicio}", (float) $kardex->opening_unit_cost);

            // Fórmula para Costo Total Entrada = Cantidad * Costo Unitario
            $hoja->setCellValue("H{$filaInicio}", "=F{$filaInicio}*G{$filaInicio}");

            // 4. Salidas (I, J, K): Quedan en 0 para la fila inicial
            $hoja->setCellValue("I{$filaInicio}", null);
            $hoja->setCellValue("J{$filaInicio}", "=M16");
            $hoja->setCellValue("K{$filaInicio}", "=I{$filaInicio}*J{$filaInicio}");

            $hoja->setCellValue("L{$filaInicio}", "=(F17+L16)-I17");
            $hoja->setCellValue("M{$filaInicio}", "=IF(L17>0,N17/L17,0)");
            $hoja->setCellValue("N{$filaInicio}", "=(N16+H17)-K17");

            // Incrementamos el contador para que los siguientes movimientos comiencen en la fila 18
            $filaInicio++;
        }


        $filaActual = $filaInicio;

        // La fila "ancla" que usarán las fórmulas de la primera fila de movimiento:
        // si hay saldo inicial, ancla en 16; si no, la primera fila se calcula sin arrastre previo
        $filaAncla = $tieneSaldoInicial ? 16 : null;

        KardexMovement::query()
            ->where('kardex_id', $kardex->id)
            ->orderBy('movement_date')
            ->orderBy('id')
            ->chunkById(200, function ($movements) use ($hoja, &$filaActual, $filaInicio, $filaAncla) {
                foreach ($movements as $mov) {
                    $rowAnt = $filaActual - 1;

                    $hoja->setCellValue("A{$filaActual}", $mov->movement_date ? $mov->movement_date->format('d/m/Y') : '');
                    $hoja->setCellValue("B{$filaActual}", $mov->document_type ?? '');
                    $hoja->setCellValue("C{$filaActual}", $mov->document_series ?? '');
                    $hoja->setCellValue("D{$filaActual}", $mov->document_number ?? '');
                    $hoja->setCellValue("E{$filaActual}", $mov->operation_type ?? '');

                    $hoja->setCellValue("F{$filaActual}", $mov->entry_qty > 0 ? (float) $mov->entry_qty : null);
                    $hoja->setCellValue("G{$filaActual}", "=IFERROR(H{$filaActual}/F{$filaActual}, \"\")");
                    $hoja->setCellValue("H{$filaActual}", $mov->entry_total_cost > 0 ? (float) $mov->entry_total_cost : 0);

                    $hoja->setCellValue("I{$filaActual}", $mov->exit_qty > 0 ? (float) $mov->exit_qty : null);
                    $hoja->setCellValue("J{$filaActual}", "=M{$rowAnt}");
                    $hoja->setCellValue("K{$filaActual}", "=I{$filaActual}*J{$filaActual}");

                    // ===== Saldo Final: fórmula UNIFORME para entrada o salida =====
                    $hoja->setCellValue("L{$filaActual}", "=(F{$filaActual}+L{$rowAnt})-I{$filaActual}");
                    $hoja->setCellValue("M{$filaActual}", "=IF(L{$filaActual}>0,N{$filaActual}/L{$filaActual},0)");
                    $hoja->setCellValue("N{$filaActual}", "=(N{$rowAnt}+H{$filaActual})-K{$filaActual}");

                    $filaActual++;
                }
            });

        $filaFin = max($filaActual - 1, $filaInicio);
        $filaBordeInicio = $tieneSaldoInicial ? 16 : $filaInicio;

        //filas g,h,j,k,m,n reducir decimales a dos de forma visual

        if ($filaFin >= $filaBordeInicio) {
            $hoja->getStyle("A{$filaBordeInicio}:N{$filaFin}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);

            // 2. Formato de 2 decimales visuales para columnas de Costos / Precios (G, H, J, K, M, N)
            $columnasMoneda = ['G', 'H', 'J', 'K', 'M', 'N'];
            foreach ($columnasMoneda as $col) {
                $hoja->getStyle("{$col}{$filaBordeInicio}:{$col}{$filaFin}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00;-#,##0.00;"-"');
            }
        }

        $folder = 'kardex/' . date('Y-m');
        $fileName = $kardex->id . '_' . Str::slug($product->name ?? 'producto') . '.xlsx';
        $relativeFilePath = "{$folder}/{$fileName}";

        Storage::disk('public')->makeDirectory($folder);
        $fullPath = Storage::disk('public')->path($relativeFilePath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $relativeFilePath;
    }
    private function resolveEntryUnitCost(StockMovement $mov, float $costoPromedioActual): float
    {
        // Camino correcto: referencia directa y sin ambigüedad al item exacto
        if ($mov->purchase_item_id) {
            $item = PurchaseItem::find($mov->purchase_item_id);

            if ($item) {
                return (float) $item->unit_cost_base;
            }
        }

        // Fallback SOLO para movimientos creados antes de este fix, que no
        // tienen purchase_item_id — puede ser ambiguo si la compra tenía
        // varias líneas del mismo producto. Recalcula backfill si es posible.
        if ($mov->source_type === Purchase::class) {
            $item = PurchaseItem::where('purchase_id', $mov->source_id)
                ->where('product_id', $mov->product_id)
                ->first();

            if ($item) {
                return (float) ($item->unit_cost_base ?: $item->unit_cost);
            }
        }

        return $costoPromedioActual;
    }
    /*
        private function resolveEntryUnitCost(StockMovement $mov, float $costoPromedioActual): float
        {
            if ($mov->source_type === Purchase::class) {
                // Buscamos el PurchaseItem correspondiente para el costo real facturado
                $item = PurchaseItem::where('purchase_id', $mov->source_id)
                    ->where('product_id', $mov->product_id)
                    ->first();

                if ($item && $item->quantity_base > 0) {
                    return round((float) $item->line_total / (float) $item->quantity_base, 6);
                }
            }

            // Sin costo de compra asociado (ej. transferencia entre almacenes,
            // devolución): se mantiene el costo promedio vigente, no se inventa uno nuevo
            return $costoPromedioActual;
        }*/
    /**
     * Permite editar el saldo inicial de un kardex mientras esté 'open'.
     * Al cambiarlo, se debe recalcular todo el periodo, porque el saldo
     * inicial es el punto de partida del cálculo en cadena (promedio ponderado).
     */
    public function updateOpeningBalance(Kardex $kardex, float $qty, float $unitCost): void
    {
        if ($kardex->status === 'closed') {
            throw new \RuntimeException('No se puede editar el saldo inicial de un kardex cerrado.');
        }

        $kardex->update([
            'opening_qty' => $qty,
            'opening_unit_cost' => $unitCost,
            'opening_total_cost' => round($qty * $unitCost, 4),
        ]);

        $this->calculate($kardex);
    }
    /**
     * Trae el saldo final del mes anterior de este mismo producto,
     * para ofrecerlo como sugerencia al editar el saldo inicial.
     */
    public function previousMonthClosingBalance(Kardex $kardex): ?array
    {
        $anterior = Kardex::where('product_id', $kardex->product_id)
            ->where(function ($q) use ($kardex) {
                $q->where('year', '<', $kardex->year)
                    ->orWhere(function ($qq) use ($kardex) {
                        $qq->where('year', $kardex->year)->where('month', '<', $kardex->month);
                    });
            })
            ->orderByDesc('year')->orderByDesc('month')
            ->first();

        if (!$anterior) {
            return null;
        }

        return [
            'qty' => (float) $anterior->closing_qty,
            'unit_cost' => (float) $anterior->closing_unit_cost,
            'period_label' => "{$anterior->month}/{$anterior->year}",
        ];
    }
    public function close(Kardex $kardex): void
    {
        if ($kardex->movements()->count() === 0 && $kardex->total_entries_qty == 0 && $kardex->total_exits_qty == 0) {
            // Permitir cerrar en cero (mes sin movimiento) es válido
        }

        $kardex->update(['status' => 'closed', 'closed_at' => now()]);
    }
}