<?php

namespace App\Livewire\ExplosiveDispatches;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\ExplosiveFieldDispatch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Despachos de explosivos')]
class ExplosiveFieldDispatchList extends Component
{
    use WithPagination;

    protected $listeners = [
        'distribution-saved' => '$refresh',
    ];

    #[Computed]
    public function myEmployeeId(): ?int
    {
        return Employee::query()
            ->where('person_id', auth()->user()->person_id)
            ->value('id');
    }

    #[Computed]
    public function restrictDistribution(): bool
    {
        return CompanySetting::current()->restrict_distribution_to_requester;
    }

    /** Misma regla que valida el formulario de distribución. */
    public function canDistribute(ExplosiveFieldDispatch $dispatch): bool
    {
        return $dispatch->canBeDistributedBy(
            auth()->user(),
            $this->myEmployeeId,
            $this->restrictDistribution,
        );
    }
    public function getDispatchesProperty()
    {
        return ExplosiveFieldDispatch::query()
            ->with([
                'dispatchedBy.person',
                'requestedBy.person',
            ])
            ->latest('dispatch_date')
            ->latest('id')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.explosive-dispatches.explosive-field-dispatch-list', [
            'dispatches' => $this->dispatches,
        ]);
    }
}