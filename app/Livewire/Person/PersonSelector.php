<?php

namespace App\Livewire\Person;

use App\Models\Person;
use Livewire\Attributes\On;
use Livewire\Component;

class PersonSelector extends Component
{
    public bool $show = false;

    public ?string $context = null;

    public string $search = '';

    public ?int $selectedPersonId = null;

    // Cuando venimos de un registro recién creado
    public ?array $justRegistered = null;

    #[On('open-person-selector')]
    public function open(?string $context = null): void
    {
        $this->reset(['search', 'selectedPersonId', 'justRegistered']);
        $this->context = $context;
        $this->show = true;
    }

    #[On('person-registered')]
    public function handlePersonRegistered(int $personId, string $displayName, string $documentNumber, ?string $context = null): void
    {
        // Solo reaccionamos si el registro viene del mismo flujo que nosotros abrimos
        if ($context !== $this->context) {
            return;
        }

        $this->justRegistered = [
            'id' => $personId,
            'display_name' => $displayName,
            'document_number' => $documentNumber,
        ];

        $this->show = true; // nos volvemos a mostrar, ahora en modo confirmación
    }

    public function getResultsProperty()
    {
        if (mb_strlen($this->search) < 2) {
            return collect();
        }

        return Person::query()
            ->where(function ($q) {
                $q->where('display_name', 'like', "%{$this->search}%")
                    ->orWhere('document_number', 'like', "%{$this->search}%");
            })
            ->limit(10)
            ->get(['id', 'display_name', 'document_type', 'document_number', 'mobile']);
    }

    public function selectRow(int $personId): void
    {
        $this->selectedPersonId = $personId;
    }

    public function confirmSelection(): void
    {
        $person = Person::findOrFail($this->selectedPersonId);

        $this->dispatch(
            'person-selected',
            personId: $person->id,
            displayName: $person->display_name,
            documentNumber: $person->document_number,
            context: $this->context,
        );

        $this->close();
    }

    public function acceptJustRegistered(): void
    {
        $this->dispatch(
            'person-selected',
            personId: $this->justRegistered['id'],
            displayName: $this->justRegistered['display_name'],
            documentNumber: $this->justRegistered['document_number'],
            context: $this->context,
        );

        $this->close();
    }

    public function openRegistrar(): void
    {
        $this->show = false;
        $this->dispatch('open-person-registrar', context: $this->context);
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset(['search', 'selectedPersonId', 'justRegistered']);
    }

    public function render()
    {
        return view('livewire.person.person-selector');
    }
}