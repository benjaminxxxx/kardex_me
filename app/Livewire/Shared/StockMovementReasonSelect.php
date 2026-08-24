<?php

namespace App\Livewire\Shared;

use App\Models\StockMovement;
use Livewire\Component;

class StockMovementReasonSelect extends Component
{
    public string $direction = 'in'; // 'in' u 'out'
    public string $reason = '';
    public bool $isCustom = false;

    public function mount(string $direction = 'in', string $reason = ''): void
    {
        $this->direction = $direction;
        $this->reason = $reason;
    }

    public function getSuggestedReasonsProperty(): array
    {
        $defaults = $this->direction === 'in' 
            ? []
            : [];

        $dbReasons = StockMovement::where('direction', $this->direction)
            ->whereNotNull('reason')
            ->where('reason', '!=', '')
            ->distinct()
            ->pluck('reason')
            ->toArray();

        return array_values(array_unique(array_merge($defaults, $dbReasons)));
    }

    public function selectReason(string $value): void
    {
        if ($value === '__custom__') {
            $this->isCustom = true;
            $this->reason = '';
            $this->dispatch('reason-updated', reason: '');
            return;
        }

        $this->isCustom = false;
        $this->reason = $value;
        $this->dispatch('reason-updated', reason: $value);
    }

    public function updatedReason($value): void
    {
        $this->dispatch('reason-updated', reason: trim($value));
    }

    public function render()
    {
        return view('livewire.shared.stock-movement-reason-select');
    }
}