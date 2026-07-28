<?php

namespace App\Livewire\Shared;

use App\Models\Employee;
use App\Models\MiningLabor;
use App\Models\Product;
use App\Models\Supplier;
use Livewire\Component;

class EntitySearchSelect extends Component
{
    public string $entityType;   // 'mining_labor' | 'employee'
    public string $fieldContext; // identificador único: "row-0-labor", "row-2-driller"
    public string $search = '';
    public ?int $selectedId = null;
    public ?string $selectedLabel = null;
    public bool $searching = true;

    public function mount(string $entityType, string $fieldContext, ?int $selectedId = null, ?string $selectedLabel = null): void
    {
        $this->entityType = $entityType;
        $this->fieldContext = $fieldContext;
        $this->selectedId = $selectedId;
        $this->selectedLabel = $selectedLabel;
        $this->searching = !$selectedId;
    }

    public function getResultsProperty()
    {
        if (mb_strlen($this->search) < 2) {
            return collect();
        }

        return match ($this->entityType) {
            'mining_labor' => MiningLabor::query()
                ->where('code', 'like', "%{$this->search}%")
                ->limit(5)->get()
                ->map(fn($m) => (object) ['id' => $m->id, 'label' => $m->code]),

            'employee' => Employee::with('person')
                ->whereHas('person', fn($q) => $q->where('display_name', 'like', "%{$this->search}%"))
                ->limit(5)->get()
                ->map(fn($e) => (object) ['id' => $e->id, 'label' => $e->person->display_name]),

            'supplier' => Supplier::with('person')
                ->whereHas('person', function ($q) {
                        $q->where('display_name', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%");
                    })
                ->limit(5)->get()
                ->map(fn($s) => (object) ['id' => $s->id, 'label' => $s->person->display_name]),

            'product' => Product::where('is_active', true)
                ->where(function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%");
                    })
                ->limit(5)->get()
                ->map(fn($p) => (object) [
                    'id' => $p->id,
                    'label' => $p->brand ? "{$p->name} ({$p->brand})" : $p->name,
                ]),

            default => collect(),
        };
    }

    public function select(int $id, string $label): void
    {
        $this->selectedId = $id;
        $this->selectedLabel = $label;
        $this->searching = false;
        $this->search = '';

        $this->dispatch('entity-selected', context: $this->fieldContext, id: $id, label: $label);
    }

    public function clear(): void
    {
        $this->reset(['selectedId', 'selectedLabel', 'search']);
        $this->searching = true;
        $this->dispatch('entity-cleared', context: $this->fieldContext);
    }

    public function render()
    {
        return view('livewire.shared.entity-search-select');
    }
}