<?php

namespace App\Livewire\MiningLabors;

use App\Models\MiningLabor;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Labores Mineras')]
class MiningLaborList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $laborType = '';

    #[Url]
    public string $veinName = '';

    #[Url]
    public string $levelNumber = '';

    public string $sortBy = 'code';
    public string $sortDirection = 'asc';
    public bool $showTrashed = false;

    protected $listeners = [
        'mining-labor-saved' => '$refresh',
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
        $this->reset('search', 'laborType', 'veinName', 'levelNumber');
    }

    public function getVeinOptionsProperty(): array
    {
        return MiningLabor::veinSuggestions();
    }

    public function getLaborsProperty()
    {
        return MiningLabor::query()
            ->when($this->showTrashed, fn ($q) => $q->onlyTrashed())
            ->when($this->search, fn ($q) => $q->where('code', 'like', "%{$this->search}%"))
            ->when($this->laborType, fn ($q) => $q->where('labor_type', $this->laborType))
            ->when($this->veinName, fn ($q) => $q->where('vein_name', $this->veinName))
            ->when($this->levelNumber, fn ($q) => $q->where('level_number', $this->levelNumber))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->dispatch('open-mining-labor-form');
    }

    public function openEdit(int $laborId): void
    {
        $this->dispatch('open-mining-labor-form', laborId: $laborId);
    }

    public function deleteLabor(int $laborId): void
    {
        MiningLabor::findOrFail($laborId)->delete();
        Flux::toast('Labor eliminada. Puedes restaurarla desde "Ver eliminados".');
    }

    public function restoreLabor(int $laborId): void
    {
        MiningLabor::onlyTrashed()->findOrFail($laborId)->restore();
        Flux::toast('Labor restaurada correctamente.');
    }

    public function forceDeleteLabor(int $laborId): void
    {
        MiningLabor::onlyTrashed()->findOrFail($laborId)->forceDelete();
        Flux::toast('Labor eliminada permanentemente.');
    }

    public function render()
    {
        return view('livewire.mining-labors.mining-labor-list', [
            'labors' => $this->labors,
        ]);
    }
}