<?php

namespace App\Livewire\Kardex;

use App\Models\Kardex;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Kardex')]
class KardexList extends Component
{
    use WithPagination;

    #[Url]
    public string $year = '';

    #[Url]
    public string $month = '';

    #[Url]
    public string $status = '';

    public ?int $productId = null;
    public ?string $productLabel = null;

    protected $listeners = ['kardex-saved' => '$refresh'];

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'kardex-filter-product') {
            $this->productId = $id;
            $this->productLabel = $label;
            $this->resetPage();
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'kardex-filter-product') {
            $this->productId = null;
            $this->productLabel = null;
            $this->resetPage();
        }
    }

    public function updatedYear(): void { $this->resetPage(); }
    public function updatedMonth(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset('year', 'month', 'status', 'productId', 'productLabel');
    }

    public function getAvailableYearsProperty()
    {
        return Kardex::selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');
    }

    public function getKardexesProperty()
    {
        return Kardex::query()
            ->with('product')
            ->when($this->year, fn ($q) => $q->where('year', $this->year))
            ->when($this->month, fn ($q) => $q->where('month', $this->month))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.kardex.kardex-list', [
            'kardexes' => $this->kardexes,
        ]);
    }
}