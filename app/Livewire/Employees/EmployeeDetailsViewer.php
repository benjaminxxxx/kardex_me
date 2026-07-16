<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeDetailsViewer extends Component
{
    public bool $show = false;
    public ?Employee $employee = null;

    #[On('open-employee-details')]
    public function open(int $employeeId): void
    {
        $this->employee = Employee::with([
            'person',
            'person.user',
            'person.user.roles',
            'person.createdBy',
            'person.updatedBy',
            'person.user.createdBy',
            'person.user.updatedBy',
            'createdBy',
            'updatedBy',
        ])->findOrFail($employeeId);

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->employee = null;
    }

    public function render()
    {
        return view('livewire.employees.employee-details-viewer');
    }
}