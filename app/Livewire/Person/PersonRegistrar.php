<?php

namespace App\Livewire\Person;

use App\Models\Person;
use Flux\Flux;
use Livewire\Attributes\On;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PersonRegistrar extends Component
{
    public bool $show = false;
    public string $mode = 'create'; // 'create' | 'edit'
    public ?string $context = null;
    public ?int $personId = null;

    // Tipo de persona: individual | company
    public string $type = 'individual';

    // Identidad (compartido, pero el tipo de documento sugerido cambia)
    public string $documentType = 'DNI';
    public string $documentNumber = '';

    // ===== Solo Persona Natural =====
    public string $names = '';
    public string $paternalLastName = '';
    public string $maternalLastName = '';
    public ?string $birthDate = null;
    public ?string $gender = null;
    public ?string $maritalStatus = null;

    // ===== Solo Empresa =====
    public string $companyName = '';   // nombre comercial
    public string $legalName = '';     // razón social

    // ===== Contacto (compartido) =====
    public string $mobile = '';
    public string $phone = '';
    public string $email = '';

    // ===== Dirección (compartido) =====
    public string $country = '';
    public string $state = '';
    public string $city = '';
    public string $district = '';
    public string $postalCode = '';
    public string $address = '';

    public string $notes = '';

    #[On('open-person-registrar')]
    public function open(?string $context = null): void
    {
        $this->resetFields();
        $this->mode = 'create';
        $this->context = $context;
        $this->show = true;
    }

    #[On('open-person-editor')]
    public function openForEdit(int $personId): void
    {
        $person = Person::findOrFail($personId);

        $this->mode = 'edit';
        $this->personId = $person->id;
        $this->type = $person->type;
        $this->documentType = $person->document_type;
        $this->documentNumber = $person->document_number;

        $this->names = $person->names ?? '';
        $this->paternalLastName = $person->paternal_last_name ?? '';
        $this->maternalLastName = $person->maternal_last_name ?? '';
        $this->birthDate = $person->birth_date?->format('Y-m-d');
        $this->gender = $person->gender;
        $this->maritalStatus = $person->marital_status;

        $this->companyName = $person->company_name ?? '';
        $this->legalName = $person->legal_name ?? '';

        $this->mobile = $person->mobile ?? '';
        $this->phone = $person->phone ?? '';
        $this->email = $person->email ?? '';

        $this->country = $person->country ?? '';
        $this->state = $person->state ?? '';
        $this->city = $person->city ?? '';
        $this->district = $person->district ?? '';
        $this->postalCode = $person->postal_code ?? '';
        $this->address = $person->address ?? '';
        $this->notes = $person->notes ?? '';

        $this->show = true;
    }

    /**
     * Se dispara al cambiar el selector de tipo (wire:model.live).
     * Limpia inmediatamente los campos del tipo contrario para que
     * el usuario vea que "desaparecieron" de verdad, no solo visualmente.
     */
    public function updatedType(string $value): void
    {
        if ($value === 'company') {
            $this->reset(['names', 'paternalLastName', 'maternalLastName', 'birthDate', 'gender', 'maritalStatus']);
            $this->documentType = 'RUC';
        } else {
            $this->reset(['companyName', 'legalName']);
            $this->documentType = 'DNI';
        }
    }

    private function resetFields(): void
    {
        $this->reset([
            'personId', 'documentNumber',
            'names', 'paternalLastName', 'maternalLastName', 'birthDate', 'gender', 'maritalStatus',
            'companyName', 'legalName',
            'mobile', 'phone', 'email',
            'country', 'state', 'city', 'district', 'postalCode', 'address', 'notes',
        ]);
        $this->type = 'individual';
        $this->documentType = 'DNI';
    }

    private function rules(): array
    {
        $documentUnique = Rule::unique('persons', 'document_number')
            ->where('document_type', $this->documentType)
            ->ignore($this->personId);

        $common = [
            'documentType' => ['required'],
            'documentNumber' => ['required', 'string', $documentUnique],
            'mobile' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'birthDate' => ['nullable', 'date'], // se valida solo si type=individual, pero no molesta si viene null
        ];

        if ($this->type === 'company') {
            return $common + [
                'companyName' => ['required', 'string', 'max:255'],
                'legalName' => ['required', 'string', 'max:255'],
            ];
        }

        return $common + [
            'names' => ['required', 'string', 'max:255'],
            'paternalLastName' => ['required', 'string', 'max:255'],
            'maternalLastName' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $this->validate($this->rules());

        // Defensa en profundidad: aunque updatedType() ya debió limpiar los campos
        // al cambiar el selector, forzamos aquí null/'' en los campos que NO
        // corresponden al tipo actual, para que nunca se persista basura cruzada.
        if ($this->type === 'company') {
            $this->names = '';
            $this->paternalLastName = '';
            $this->maternalLastName = '';
            $this->birthDate = null;
            $this->gender = null;
            $this->maritalStatus = null;
        } else {
            $this->companyName = '';
            $this->legalName = '';
        }

        try {
            $displayName = $this->type === 'company'
                ? $this->companyName
                : trim("{$this->names} {$this->paternalLastName} {$this->maternalLastName}");

            $payload = [
                'type' => $this->type,
                'document_type' => $this->documentType,
                'document_number' => $this->documentNumber,

                'names' => $this->type === 'individual' ? $this->names : null,
                'paternal_last_name' => $this->type === 'individual' ? $this->paternalLastName : null,
                'maternal_last_name' => $this->type === 'individual' ? $this->maternalLastName : null,
                'birth_date' => $this->type === 'individual' ? $this->birthDate : null,
                'gender' => $this->type === 'individual' ? $this->gender : null,
                'marital_status' => $this->type === 'individual' ? $this->maritalStatus : null,

                'company_name' => $this->type === 'company' ? $this->companyName : null,
                'legal_name' => $this->type === 'company' ? $this->legalName : null,

                'display_name' => $displayName,

                'mobile' => $this->mobile,
                'phone' => $this->phone,
                'email' => $this->email,

                'country' => $this->country,
                'state' => $this->state,
                'city' => $this->city,
                'district' => $this->district,
                'postal_code' => $this->postalCode,
                'address' => $this->address,
                'notes' => $this->notes,
            ];

            if ($this->mode === 'edit') {
                Person::findOrFail($this->personId)->update($payload);
                $this->dispatch('person-edited');
                Flux::toast('Información actualizada correctamente.');
                $this->show = false;
                return;
            }

            $payload['code'] = 'P-'.str_pad((string) (Person::max('id') + 1), 6, '0', STR_PAD_LEFT);
            $person = Person::create($payload);

            $this->dispatch(
                'person-registered',
                personId: $person->id,
                displayName: $person->display_name,
                documentNumber: $person->document_number,
                context: $this->context,
            );
            Flux::toast('Persona registrada correctamente.');
            $this->show = false;
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function cancel(): void
    {
        $this->show = false;

        if ($this->mode === 'create') {
            $this->dispatch('open-person-selector', context: $this->context);
        }
    }

    public function render()
    {
        return view('livewire.person.person-registrar');
    }
}