<?php

namespace App\Livewire\Kardex;

use App\Models\Kardex;
use App\Models\KardexMovement;
use App\Services\KardexService;
use App\Support\ExcelHelper;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Livewire\Attributes\Title;
use Livewire\Component;

class KardexShow extends Component
{
    public function exportToExcel()
    {
        try {
            $kardex = $this->kardex;
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
                $hoja->setCellValue("I{$filaInicio}", 0);
                $hoja->setCellValue("J{$filaInicio}", 0);
                $hoja->setCellValue("K{$filaInicio}", 0);

                // 5. Saldo Final (L, M, N) de la primera fila:
                // L = Cantidad Entrada (F)
               $hoja->setCellValue("L16", "=(F16+0)-I16");
                $hoja->setCellValue("M16", "=IF(L16>0,N16/L16,0)");
                $hoja->setCellValue("N16", "=(0+H16)-K16");
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
                        $esPrimeraFila = $filaActual === $filaInicio;
                        $rowAnt = $esPrimeraFila ? $filaAncla : $filaActual - 1;

                        $hoja->setCellValue("A{$filaActual}", $mov->movement_date ? $mov->movement_date->format('d/m/Y') : '');
                        $hoja->setCellValue("B{$filaActual}", $mov->document_type ?? '');
                        $hoja->setCellValue("C{$filaActual}", $mov->document_series ?? '');
                        $hoja->setCellValue("D{$filaActual}", $mov->document_number ?? '');
                        $hoja->setCellValue("E{$filaActual}", $mov->operation_type ?? '');

                        $hoja->setCellValue("F{$filaActual}", $mov->entry_qty > 0 ? (float) $mov->entry_qty : 0);
                        $hoja->setCellValue("G{$filaActual}", "=IFERROR(H{$filaActual}/F{$filaActual}, \"\")");
                        $hoja->setCellValue("H{$filaActual}", $mov->entry_total_cost > 0 ? (float) $mov->entry_total_cost : 0);

                        $hoja->setCellValue("I{$filaActual}", $mov->exit_qty > 0 ? (float) $mov->exit_qty : 0);
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

            if ($filaFin >= $filaBordeInicio) {
                $hoja->getStyle("A{$filaBordeInicio}:N{$filaFin}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
            }

            $folder = 'kardex/' . date('Y-m');
            $fileName = $kardex->id . '_' . Str::slug($product->name ?? 'producto') . '.xlsx';
            $relativeFilePath = "{$folder}/{$fileName}";

            Storage::disk('public')->makeDirectory($folder);
            $fullPath = Storage::disk('public')->path($relativeFilePath);

            $writer = new Xlsx($spreadsheet);
            $writer->save($fullPath);

            $kardex->excel_path = $relativeFilePath;
            $kardex->save();

            Flux::toast('El kardex se generó y guardó correctamente.', 'success');

            return $relativeFilePath;

        } catch (\Throwable $th) {
            Flux::toast('Error al exportar a Excel: ' . $th->getMessage(), 'error');
        }
    }
}