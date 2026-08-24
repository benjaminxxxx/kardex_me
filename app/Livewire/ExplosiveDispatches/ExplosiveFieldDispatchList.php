<?php

namespace App\Livewire\ExplosiveDispatches;

use App\Constants\Permisos;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\ExplosiveFieldDispatch;
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

    public function getMyEmployeeIdProperty(): ?int
    {
        return Employee::query()
            ->where('person_id', auth()->user()->person_id)
            ->value('id');
    }
    public function getRestrictDistributionProperty(): bool
    {
        return CompanySetting::current()->restrict_distribution_to_requester;
    }
    public function canDistribute(ExplosiveFieldDispatch $dispatch): bool
    {
        if (!$dispatch->isPendingDistribution()) {
            return false;
        }

        if ($this->restrictDistribution) {
            return $dispatch->requested_by_employee_id === $this->myEmployeeId;
        }

        return auth()->user()->can(Permisos::EXPLOSIVOS_DISTRIBUIR);
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