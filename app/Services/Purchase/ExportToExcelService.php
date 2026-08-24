<?php

namespace App\Services\Purchase;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\ExcelHelper;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportToExcelService
{
    /**
     * Ejecuta la exportación a Excel aplicando los filtros recibidos.
     *
     * @param array $filters
     * @return StreamedResponse
     * @throws Exception
     */
    public function execute(array $filters): StreamedResponse
    {
        $spreadsheet = ExcelHelper::loadTemplate('rpt_lista_compras.xlsx');

        $hojaCompras = $spreadsheet->getSheetByName('COMPRAS');
        $hojaDetalles = $spreadsheet->getSheetByName('DETALLE_COMPRAS');

        if (!$hojaCompras || !$hojaDetalles) {
            throw new Exception("La plantilla debe contener las hojas 'COMPRAS' y 'DETALLE_COMPRAS'.");
        }

        // Extracto de variables
        $search = $filters['search'] ?? '';
        $documentType = $filters['documentType'] ?? '';
        $dateFrom = $filters['dateFrom'] ?? '';
        $dateTo = $filters['dateTo'] ?? '';
        $supplierId = $filters['supplierId'] ?? null;
        $supplierLabel = $filters['supplierLabel'] ?? null;
        $showTrashed = $filters['showTrashed'] ?? false;
        $sortBy = $filters['sortBy'] ?? 'document_date';
        $sortDirection = $filters['sortDirection'] ?? 'desc';

        // ============ REGISTRO DE FILTROS EN EXCEL ============
        $hojaCompras->setCellValue('B2', $search ?: 'Todos');
        $hojaCompras->setCellValue('B3', $documentType ? strtoupper($documentType) : 'Todos');

        $proveedorTexto = 'Todos';
        if ($supplierId) {
            $proveedor = Supplier::with('person')->find($supplierId);
            $proveedorTexto = $proveedor?->person?->display_name ?? $supplierLabel ?? 'Todos';
        }
        $hojaCompras->setCellValue('D2', $proveedorTexto);

        $rangoFechas = 'Todas';
        if ($dateFrom && $dateTo) {
            $rangoFechas = "Desde {$dateFrom} hasta {$dateTo}";
        } elseif ($dateFrom) {
            $rangoFechas = "Desde {$dateFrom}";
        } elseif ($dateTo) {
            $rangoFechas = "Hasta {$dateTo}";
        }
        $hojaCompras->setCellValue('D3', $rangoFechas);

        // ============ CONSULTA A LA BASE DE DATOS ============
        $query = Purchase::query()
            ->with([
                'supplier.person',
                'warehouse',
                'items.product',
                'items.presentation.unit',
            ])
            ->when($showTrashed, fn ($q) => $q->onlyTrashed())
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('document_number', 'like', "%{$search}%")
                        ->orWhere('document_series', 'like', "%{$search}%")
                        ->orWhereHas('supplier.person', fn ($p) => $p->where('display_name', 'like', "%{$search}%"));
                });
            })
            ->when($documentType, fn ($q) => $q->where('document_type', $documentType))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($dateFrom, fn ($q) => $q->whereDate('document_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('document_date', '<=', $dateTo))
            ->orderBy($sortBy, $sortDirection);

        $filaCompra = 6;
        $filaDetalle = 6;

        $query->chunkById(200, function ($compras) use ($hojaCompras, &$filaCompra, $hojaDetalles, &$filaDetalle) {
            foreach ($compras as $compra) {

                $documento = trim("{$compra->document_series}-{$compra->document_number}", '-');
                $proveedorNombre = $compra->supplier?->person?->display_name ?? '';
                $almacenNombre = $compra->warehouse?->name ?? '';

                // HOJA 1: COMPRAS
                $hojaCompras->setCellValue("A{$filaCompra}", $filaCompra - 5);
                ExcelHelper::setFechaCell($hojaCompras, "B{$filaCompra}", $compra->document_date, false);
                ExcelHelper::setFechaCell($hojaCompras, "C{$filaCompra}", $compra->due_date, false);
                $hojaCompras->setCellValue("D{$filaCompra}", strtoupper($compra->document_type));
                $hojaCompras->setCellValueExplicit("E{$filaCompra}", $documento, DataType::TYPE_STRING);
                $hojaCompras->setCellValue("F{$filaCompra}", $proveedorNombre);
                $hojaCompras->setCellValue("G{$filaCompra}", $almacenNombre);
                $hojaCompras->setCellValue("H{$filaCompra}", $compra->currency);
                $hojaCompras->setCellValue("I{$filaCompra}", (float) $compra->exchange_rate);
                $hojaCompras->setCellValue("J{$filaCompra}", ucfirst($compra->payment_method));
                $hojaCompras->setCellValue("K{$filaCompra}", (float) $compra->subtotal_neto);
                $hojaCompras->setCellValue("L{$filaCompra}", (float) $compra->igv_total);
                $hojaCompras->setCellValue("M{$filaCompra}", (float) $compra->total);
                $hojaCompras->setCellValue("N{$filaCompra}", $compra->created_by_name ?? '');
                $hojaCompras->setCellValue("O{$filaCompra}", $compra->notes ?? '');

                $filaCompra++;

                // HOJA 2: DETALLE_COMPRAS
                foreach ($compra->items as $item) {
                    $hojaDetalles->setCellValue("A{$filaDetalle}", $filaDetalle - 5);
                    $hojaDetalles->setCellValueExplicit("B{$filaDetalle}", $documento, DataType::TYPE_STRING);
                    $hojaDetalles->setCellValue("C{$filaDetalle}", $proveedorNombre);
                    $hojaDetalles->setCellValue("D{$filaDetalle}", $item->product?->name ?? '');
                    $hojaDetalles->setCellValue("E{$filaDetalle}", $item->presentation?->name ?? '');
                    $hojaDetalles->setCellValue("F{$filaDetalle}", $item->presentation?->unit?->sunat_code ?? '');
                    $hojaDetalles->setCellValue("G{$filaDetalle}", (float) $item->quantity);
                    $hojaDetalles->setCellValue("H{$filaDetalle}", (float) $item->unit_cost);
                    $hojaDetalles->setCellValue("I{$filaDetalle}", (float) $item->discount_percent);
                    $hojaDetalles->setCellValue("J{$filaDetalle}", (float) $item->igv_percent);
                    $hojaDetalles->setCellValue("K{$filaDetalle}", (float) $item->line_total);
                    $hojaDetalles->setCellValue("L{$filaDetalle}", (float) $item->quantity_base);
                    $hojaDetalles->setCellValue("M{$filaDetalle}", (float) $item->unit_cost_base);

                    $filaDetalle++;
                }
            }
        });

        ExcelHelper::aplicarBordesRango($hojaCompras, 'A6:O' . max($filaCompra - 1, 6));
        ExcelHelper::aplicarBordesRango($hojaDetalles, 'A6:M' . max($filaDetalle - 1, 6));

        return ExcelHelper::download($spreadsheet, 'REPORTE_COMPRAS_' . date('Ymd_His') . '.xlsx');
    }
}