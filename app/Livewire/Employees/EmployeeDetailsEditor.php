<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeDetailsEditor extends Component
{
    public bool $show = false;
    public ?int $employeeId = null;
    public ?string $displayName = null;

    public string $hireDate = '';
    public ?string $terminationDate = null;
    public string $status = 'active';
    public string $notes = '';

    #[On('open-employee-editor')]
    public function open(int $employeeId): void
    {
        $employee = Employee::with('person')->findOrFail($employeeId);

        $this->employeeId = $employee->id;
        $this->displayName = $employee->person->display_name;
        $this->hireDate = $employee->hire_date->format('Y-m-d');
        $this->terminationDate = $employee->termination_date?->format('Y-m-d');
        $this->status = $employee->status;
        $this->notes = $employee->notes ?? '';
        $this->show = true;
    }

    public function save(): void
    {
        $this->validate([
            'hireDate' => ['required', 'date'],
            'terminationDate' => ['nullable', 'date', 'after_or_equal:hireDate'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
        ]);

        try {
            // Regla de negocio: si marca "terminated" pero no puso fecha de cese, se la pedimos
            if ($this->status === 'terminated' && !$this->terminationDate) {
                Flux::toast('Debes indicar la fecha de cese si el estado es "Cesado".', 'Error');
                return;
            }

            Employee::findOrFail($this->employeeId)->update([
                'hire_date' => $this->hireDate,
                'termination_date' => $this->terminationDate,
                'status' => $this->status,
                'notes' => $this->notes,
            ]);
            $this->dispatch(
                'employee-edited',
            );

            $this->show = false;
            Flux::toast('Datos laborales actualizados.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.employees.employee-details-editor');
    }
}