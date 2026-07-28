<?php

namespace App\Livewire\ExplosiveDispatches;

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