<?php

namespace App\Livewire\StockMovements;

use App\Services\StockService;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class StockMovementCreateModal extends Component
{
    public bool $show = false;
    public string $direction = 'in';
    public string $key = '';
    public ?int $warehouseId = null;
    public ?int $productId = null;
    public ?string $productLabel = null;
    public ?float $quantity = null;
    public string $movementDate = '';
    public string $reason = '';

    protected function rules(): array
    {
        return [
            'warehouseId'  => 'required|exists:warehouses,id',
            'productId'    => 'required|exists:products,id',
            'quantity'     => 'required|numeric|gt:0',
            'movementDate' => 'required|date',
            'reason'       => 'required|string|max:150',
        ];
    }

    protected $messages = [
        'warehouseId.required' => 'Seleccione un almacén.',
        'productId.required'   => 'Seleccione un producto.',
        'quantity.required'    => 'Ingrese la cantidad.',
        'quantity.gt'          => 'La cantidad debe ser mayor a 0.',
        'reason.required'      => 'Ingrese o seleccione un motivo.',
    ];

    #[On('open-movement-modal')]
    public function open(string $direction = 'in'): void
    {
        $this->resetValidation();
        $this->reset(['warehouseId', 'productId', 'productLabel', 'quantity', 'reason']);
        
        $this->direction = $direction;
        $this->movementDate = now()->format('Y-m-d');
        $this->key = uniqid('modal_', true);
        $this->show = true;
    }

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'manual-movement-product') {
            $this->productId = $id;
            $this->productLabel = $label;
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'manual-movement-product') {
            $this->productId = null;
            $this->productLabel = null;
        }
    }

    #[On('reason-updated')]
    public function handleReasonUpdated(string $reason): void
    {
        $this->reason = $reason;
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(['warehouseId', 'productId', 'productLabel', 'quantity', 'reason']);
        $this->show = false;
    }

    public function save(StockService $stockService): void
    {
        $this->validate();

        try {
            $stockService->registerMovement(
                direction: $this->direction,
                productId: $this->productId,
                warehouseId: $this->warehouseId,
                quantity: (float) $this->quantity,
                movementDate: $this->movementDate,
                sourceType: null,
                sourceId: null,
                extra: ['reason' => $this->reason]
            );

            Flux::toast(
                $this->direction === 'in' ? 'Entrada de stock registrada.' : 'Salida de stock registrada.',
                'success'
            );

            $this->close();
            $this->dispatch('movement-created');

        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'error');
        }
    }

    public function getWarehousesProperty()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.stock-movements.stock-movement-create-modal');
    }
}