<?php

namespace App\Livewire\StockMovements;

use App\Models\StockMovement;
use App\Models\Warehouse;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Movimientos de stock')]
class StockMovementList extends Component
{
    use WithPagination;

    #[Url]
    public string $direction = '';

    #[Url]
    public string $warehouseId = '';

    #[Url]
    public string $year = '';

    #[Url]
    public string $month = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $productId = null;
    public ?string $productLabel = null;

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'movement-filter-product') {
            $this->productId = $id;
            $this->productLabel = $label;
            $this->resetPage();
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'movement-filter-product') {
            $this->productId = null;
            $this->productLabel = null;
            $this->resetPage();
        }
    }

    public function updatedDirection(): void { $this->resetPage(); }
    public function updatedWarehouseId(): void { $this->resetPage(); }
    public function updatedYear(): void { $this->resetPage(); }
    public function updatedMonth(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(
            'direction', 'warehouseId', 'year', 'month',
            'dateFrom', 'dateTo', 'productId', 'productLabel'
        );
    }

    public function getWarehousesProperty()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get();
    }

    public function getAvailableYearsProperty()
    {
        return StockMovement::selectRaw('DISTINCT YEAR(movement_date) as y')
            ->orderByDesc('y')
            ->pluck('y');
    }

    public function getMovementsProperty()
    {
        return StockMovement::query()
            ->with(['product', 'warehouse'])
            ->when($this->direction, fn ($q) => $q->where('direction', $this->direction))
            ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->year, fn ($q) => $q->whereYear('movement_date', $this->year))
            ->when($this->month, fn ($q) => $q->whereMonth('movement_date', $this->month))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('movement_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('movement_date', '<=', $this->dateTo))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.stock-movements.stock-movement-list', [
            'movements' => $this->movements,
        ]);
    }
}