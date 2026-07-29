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
    public function exportToExcel2()
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

            // ===== Fila 16: Saldo inicial (código de operación 16, Tabla 12), SOLO si es distinto de cero =====
            // Esta fila es el ANCLA fija: las fórmulas de la primera fila de movimientos
            // (fila 17) siempre referencian la fila 16, exista o no saldo inicial real.
            $tieneSaldoInicial = (float) $kardex->opening_qty > 0;
            $filaInicio = 17;

            if ($tieneSaldoInicial) {
                // 1. Primer día del mes correspondiente al Kardex (ejemplo: 01/05/2026)
                $fechaInicioMes = sprintf('01/%02d/%04d', $kardex->month, $kardex->year);

                // 2. Columna de Documentos (A-D): sin comprobante, es el arrastre de apertura
                $hoja->setCellValue("A16", $fechaInicioMes);
                $hoja->setCellValue("B16", '');
                $hoja->setCellValue("C16", '');
                $hoja->setCellValue("D16", '');
                $hoja->setCellValue("E16", '16'); // Tabla 12: código 16 = Saldo Inicial

                // 3. Entradas (F, G, H): se registra la apertura como entrada de inventario
                $hoja->setCellValue("F16", (float) $kardex->opening_qty);
                // G = costo unitario entrada = Costo Total / Cantidad
                $hoja->setCellValue("G16", "=IFERROR(H16/F16, \"\")");
                // H = Costo Total (dato conocido: opening_total_cost)
                $hoja->setCellValue("H16", (float) $kardex->opening_total_cost);

                // 4. Salidas (I, J, K): no hay salida en la fila de apertura
                $hoja->setCellValue("I16", 0);
                $hoja->setCellValue("J16", 0);
                $hoja->setCellValue("K16", 0);

                // 5. Saldo Final (L, M, N) de la fila de apertura:
                // al no existir fila anterior, se asume saldo previo = 0
                $hoja->setCellValue("L16", "=(F16+0)-I16");
                $hoja->setCellValue("M16", "=IF(L16>0,N16/L16,0)");
                $hoja->setCellValue("N16", "=(0+H16)-K16");
            }

            $filaActual = $filaInicio;

            // La fila "ancla" que usarán las fórmulas de la primera fila de movimiento:
            // si hay saldo inicial, ancla en 16; si no, la primera fila también ancla en 16
            // pero con L16/N16 en blanco/0 (fila sin poblar), de modo que la fórmula
            // sigue siendo uniforme sin necesitar una rama especial "sin arrastre".
            if (!$tieneSaldoInicial) {
                $hoja->setCellValue("L16", 0);
                $hoja->setCellValue("N16", 0);
            }
            $filaAncla = 16;

            KardexMovement::query()
                ->where('kardex_id', $kardex->id)
                ->orderBy('movement_date')
                ->orderBy('id')
                ->chunkById(200, function ($movements) use ($hoja, &$filaActual, $filaAncla) {
                    foreach ($movements as $mov) {
                        $rowAnt = $filaActual === 17 ? $filaAncla : $filaActual - 1;

                        $hoja->setCellValue("A{$filaActual}", $mov->movement_date ? $mov->movement_date->format('d/m/Y') : '');
                        $hoja->setCellValue("B{$filaActual}", $mov->document_type ?? '');
                        $hoja->setCellValue("C{$filaActual}", $mov->document_series ?? '');
                        $hoja->setCellValue("D{$filaActual}", $mov->document_number ?? '');
                        $hoja->setCellValue("E{$filaActual}", $mov->operation_type ?? '');

                        // ===== Entradas: solo se pueblan si hubo entrada real en esta fila =====
                        $hoja->setCellValue("F{$filaActual}", $mov->entry_qty > 0 ? (float) $mov->entry_qty : 0);
                        // G = costo unitario entrada = Costo Total / Cantidad
                        $hoja->setCellValue("G{$filaActual}", "=IFERROR(H{$filaActual}/F{$filaActual}, \"\")");
                        $hoja->setCellValue("H{$filaActual}", $mov->entry_total_cost > 0 ? (float) $mov->entry_total_cost : 0);

                        // ===== Salidas: cantidad solo si hubo salida real; costo unitario
                        // SIEMPRE toma el saldo final (costo unitario) de la fila anterior,
                        // sin importar si esta fila es una entrada o una salida =====
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

            $filaFin = max($filaActual - 1, $filaInicio - 1);

            // ===== Fila de Totales: solo suma columnas de movimiento (Entradas/Salidas),
            // NUNCA se totaliza costo unitario ni saldo final (no tiene sentido sumar
            // un promedio ni un saldo acumulado) =====
            $filaTotales = $filaFin + 1;
            $rangoDatos = "17:{$filaFin}"; // excluye la fila 16 (saldo inicial no es un "movimiento" del periodo)

            if ($filaFin >= 17) {
                $hoja->setCellValue("E{$filaTotales}", 'TOTALES');
                $hoja->setCellValue("F{$filaTotales}", "=SUM(F{$rangoDatos})");
                $hoja->setCellValue("H{$filaTotales}", "=SUM(H{$rangoDatos})");
                $hoja->setCellValue("I{$filaTotales}", "=SUM(I{$rangoDatos})");
                $hoja->setCellValue("K{$filaTotales}", "=SUM(K{$rangoDatos})");
                // G, J, L, M, N quedan sin fórmula: no se totalizan costos unitarios ni saldos
            }

            // ===== Bordes: desde la fila 16 (o 17 si no hay saldo inicial) hasta la fila de totales =====
            $filaBordeInicio = $tieneSaldoInicial ? 16 : 17;
            $filaBordeFin = $filaFin >= 17 ? $filaTotales : $filaFin;

            if ($filaBordeFin >= $filaBordeInicio) {
                $hoja->getStyle("A{$filaBordeInicio}:N{$filaBordeFin}")->applyFromArray([
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