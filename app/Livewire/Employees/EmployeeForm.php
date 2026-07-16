<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeForm extends Component
{
    public int $step = 1;

    // Datos que vienen del selector
    public ?int $personId = null;
    public ?string $personDisplayName = null;
    public ?string $personDocumentNumber = null;

    // Paso 2
    public string $hireDate = '';
    public string $status = 'active';
    public string $notes = '';

    public function mount(): void
    {
        $this->dispatch('open-person-selector', context: 'employee-wizard');
    }

    #[On('person-selected')]
    public function personSelected(int $personId, string $displayName, string $documentNumber, ?string $context = null): void
    {
        if ($context !== 'employee-wizard') {
            return;
        }

        // Bloqueo: si ya es empleado, no dejamos avanzar
        $existing = Employee::withTrashed()->where('person_id', $personId)->first();

        if ($existing) {
            
            Flux::toast('Esta persona ya está registrada como empleado.', 'Error');
            $this->redirect(route('employees.index'), navigate: true);
            return;
        }

        $this->personId = $personId;
        $this->personDisplayName = $displayName;
        $this->personDocumentNumber = $documentNumber;
        $this->step = 2;
    }

    public function changePerson(): void
    {
        $this->reset(['personId', 'personDisplayName', 'personDocumentNumber']);
        $this->step = 1;
        $this->dispatch('open-person-selector', context: 'employee-wizard');
    }

    public function save(): void
    {
        $this->validate([
            'hireDate' => ['required', 'date'],
            'status' => ['required'],
        ]);

        Employee::create([
            'person_id' => $this->personId,
            'employee_code' => $this->generateCode(),
            'hire_date' => $this->hireDate,
            'status' => $this->status,
            'notes' => $this->notes,
        ]);

        Flux::toast('Empleado registrado correctamente.');
        $this->redirect(route('employees.index'), navigate: true);
    }

    private function generateCode(): string
    {
        $last = Employee::withTrashed()->max('id') + 1;

        return 'EMP-'.str_pad((string) $last, 5, '0', STR_PAD_LEFT);
    }

   
    public function render()
    {
        return view('livewire.employees.employee-form');
    }
}