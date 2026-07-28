<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\Warehouse;
use App\Support\ExcelHelper;
use Exception;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

#[Title('Productos')]
class ProductList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryId = '';

    public string $sortBy = 'code';
    public string $sortDirection = 'asc';
    public bool $showTrashed = false;

    protected $listeners = [
        'product-saved' => '$refresh',
    ];

    public function sort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'categoryId');
    }

    public function getCategoryOptionsProperty()
    {
        return \App\Models\ProductCategory::where('is_active', true)->orderBy('name')->get();
    }

    public function getProductsProperty()
    {
        return Product::query()
            ->with(['category', 'unit', 'explosiveRole', 'stocks.warehouse'])
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%")
                        ->orWhere('sunat_product_code', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }
    public function getWarehousesProperty()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get();
    }

    public function openTransferModal(int $productId): void
    {
        $this->dispatch('open-warehouse-transfer', productId: $productId);
    }
    public function viewDetails(int $productId): void
    {
        $this->dispatch('open-product-details', productId: $productId);
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if ($product->explosive_role_id) {
            Flux::toast('Este producto está asignado como ingrediente de armadas ('
                . $product->explosiveRole->name
                . '). Debes quitarle el rol antes de eliminarlo.', 'Error');
            return;
        }

        $product->delete();
        Flux::toast('Producto eliminado. Puedes restaurarlo desde "Ver eliminados".');
    }

    public function restoreProduct(int $productId): void
    {
        Product::onlyTrashed()->findOrFail($productId)->restore();
        Flux::toast('Producto restaurado correctamente.');
    }

    public function exportToExcel()
    {
        try {
            $spreadsheet = ExcelHelper::loadTemplate('rpt_lista_productos.xlsx');

            $hojaProductos = $spreadsheet->getSheetByName('PRODUCTOS');
            $hojaPresentaciones = $spreadsheet->getSheetByName('PRESENTACIONES');

            if (!$hojaProductos || !$hojaPresentaciones) {
                throw new Exception("La plantilla debe contener las hojas 'PRODUCTOS' y 'PRESENTACIONES'.");
            }

            $filaProducto = 5;
            $filaPresentacion = 5;

            Product::query()
                ->with([
                    'category',
                    'unit',
                    'presentations.unit',
                    'createdBy',
                    'updatedBy',
                ])
                ->orderBy('id')
                ->chunkById(200, function ($productos) use ($hojaProductos, &$filaProducto, $hojaPresentaciones, &$filaPresentacion) {
                    foreach ($productos as $producto) {

                        $nombreProducto = $producto->name;

                        // ============ HOJA: PRODUCTOS ============
                        $hojaProductos->setCellValue("A{$filaProducto}", $filaProducto - 4);
                        $hojaProductos->setCellValue("B{$filaProducto}", $producto->code);
                        $hojaProductos->setCellValue("C{$filaProducto}", $producto->name);
                        $hojaProductos->setCellValue("D{$filaProducto}", $producto->chemical_name ?? '');
                        $hojaProductos->setCellValue("E{$filaProducto}", $producto->brand ?? '');
                        $hojaProductos->setCellValue("F{$filaProducto}", $producto->category->name ?? '');
                        $hojaProductos->setCellValue("G{$filaProducto}", $producto->unit->sunat_code ?? '');
                        $hojaProductos->setCellValueExplicit(
                            "H{$filaProducto}",
                            $producto->barcode ?? '',
                            DataType::TYPE_STRING
                        );
                        $hojaProductos->setCellValueExplicit(
                            "I{$filaProducto}",
                            $producto->sunat_product_code ?? '',
                            DataType::TYPE_STRING
                        );
                        $hojaProductos->setCellValue("J{$filaProducto}", $producto->notes ?? '');
                        $hojaProductos->setCellValue("K{$filaProducto}", $producto->is_active ? 'Activo' : 'Inactivo');

                        ExcelHelper::setFechaCell($hojaProductos, "L{$filaProducto}", $producto->created_at, true);
                        ExcelHelper::setFechaCell($hojaProductos, "M{$filaProducto}", $producto->updated_at, true);

                        $hojaProductos->setCellValue("N{$filaProducto}", $producto->created_by_name ?? '');
                        $hojaProductos->setCellValue("O{$filaProducto}", $producto->updated_by_name ?? '');

                        $filaProducto++;

                        // ============ HOJA: PRESENTACIONES ============
                        foreach ($producto->presentations as $presentacion) {
                            $hojaPresentaciones->setCellValue("A{$filaPresentacion}", $filaPresentacion - 4);
                            $hojaPresentaciones->setCellValue("B{$filaPresentacion}", $nombreProducto);
                            $hojaPresentaciones->setCellValue("C{$filaPresentacion}", $presentacion->unit->sunat_code ?? '');
                            $hojaPresentaciones->setCellValue("D{$filaPresentacion}", $presentacion->name);
                            $hojaPresentaciones->setCellValue("E{$filaPresentacion}", (float) $presentacion->conversion_factor);
                            $hojaPresentaciones->setCellValue("F{$filaPresentacion}", $presentacion->is_default_purchase ? 'Sí' : 'No');
                            $hojaPresentaciones->setCellValue("G{$filaPresentacion}", $presentacion->is_active ? 'Sí' : 'No');

                            $filaPresentacion++;
                        }
                    }
                });

            ExcelHelper::aplicarBordesRango($hojaProductos, 'A5:O' . max($filaProducto - 1, 5));
            ExcelHelper::aplicarBordesRango($hojaPresentaciones, 'A5:G' . max($filaPresentacion - 1, 5));

            return ExcelHelper::download($spreadsheet, 'REPORTE_PRODUCTOS.xlsx');

        } catch (\Throwable $th) {
            Flux::toast('Error al exportar a Excel: ' . $th->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('livewire.products.product-list', [
            'products' => $this->products,
        ]);
    }
}