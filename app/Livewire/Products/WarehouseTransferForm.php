<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockDisplayService;
use App\Services\StockService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseTransferForm extends Component
{
    public bool $showModalTransfer = false;
    public ?int $productId = null;
    public ?Product $product = null;

    public ?int $fromWarehouseId = null;
    public ?int $toWarehouseId = null;
    public string $quantity = '';
    public string $transferDate = '';
    public string $presentationId = '';     // '' = unidad base, o el id de una product_presentation


    #[On('open-warehouse-transfer')]
    public function open(int $productId): void
    {
        $this->fromWarehouseId = null;
        $this->toWarehouseId = null;
        $this->quantity = '';
        $this->presentationId = '';
        $this->productId = $productId;
        $this->product = Product::with(['unit', 'presentations.unit', 'stocks.warehouse'])->findOrFail($productId);
        $this->transferDate = now()->format('Y-m-d');
        $this->showModalTransfer = true;
    }
    public function getActivePresentationsProperty()
    {
        return $this->product?->presentations->where('is_active', true) ?? collect();
    }
    public function getWarehousesProperty()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get();
    }
    /**
     * Cantidad ya convertida a unidad base, lista para guardar/validar.
     * Si no se eligió presentación, se asume que ya está en unidad base.
     */
    public function getQuantityInBaseUnitProperty(): float
    {
        if (!is_numeric($this->quantity)) {
            return 0;
        }

        if ($this->presentationId === '') {
            return (float) $this->quantity;
        }

        $presentacion = $this->activePresentations->firstWhere('id', (int) $this->presentationId);

        if (!$presentacion) {
            return (float) $this->quantity;
        }

        return (float) $this->quantity * (float) $presentacion->conversion_factor;
    }

    public function getQuantityPreviewProperty(): ?string
    {
        if (!is_numeric($this->quantity) || (float) $this->quantity <= 0) {
            return null;
        }

        $unidadBase = $this->product->unit->alias ?: $this->product->unit->name;
        $total = $this->quantityInBaseUnit;

        if ($this->presentationId === '') {
            return null; // ya está en unidad base, no hace falta aclarar nada
        }

        return "= {$total} {$unidadBase}";
    }
    public function getAvailableInFromWarehouseProperty(): ?float
    {
        if (!$this->fromWarehouseId || !$this->product) {
            return null;
        }
        return StockService::available($this->productId, $this->fromWarehouseId);
    }

    public function getAvailableBreakdownProperty(): ?string
    {
        if ($this->availableInFromWarehouse === null) {
            return null;
        }
        return StockDisplayService::breakdown($this->product, $this->availableInFromWarehouse);
    }

    private function rules(): array
    {
        return [
            'fromWarehouseId' => ['required', 'exists:warehouses,id'],
            'toWarehouseId' => ['required', 'exists:warehouses,id', 'different:fromWarehouseId'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'transferDate' => ['required', 'date'],
        ];
    }

    public function save(StockService $service): void
    {
        $this->validate($this->rules());

        $cantidadBase = $this->quantityInBaseUnit;

        if ($cantidadBase <= 0) {
            Flux::toast('La cantidad convertida debe ser mayor a cero.', 'Error');
            return;
        }

        $disponible = StockService::available($this->productId, (int) $this->fromWarehouseId);

        if ($cantidadBase > $disponible) {
            Flux::toast("Stock insuficiente en el almacén de origen. Disponible: {$disponible}.", 'Error');
            return;
        }

        try {
            $service->transfer(
                $this->productId,
                (int) $this->fromWarehouseId,
                (int) $this->toWarehouseId,
                $cantidadBase, // siempre se guarda en unidad base, nunca en la presentación elegida
                $this->transferDate
            );

            Flux::toast('Transferencia registrada correctamente.');
            $this->dispatch('product-saved');
            $this->showModalTransfer = false;
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function close(): void
    {
        $this->showModalTransfer = false;
    }

    public function render()
    {
        return view('livewire.products.warehouse-transfer-form');
    }
}