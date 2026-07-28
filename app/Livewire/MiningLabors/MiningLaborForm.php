<?php

namespace App\Livewire\MiningLabors;

use App\Models\MiningLabor;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class MiningLaborForm extends Component
{
    public bool $show = false;
    public string $mode = 'create'; // create | edit
    public ?int $laborId = null;
    public ?string $context = null;

    public string $laborType = 'tajo';
    public string $veinName = '';
    public ?int $levelNumber = null;
    public ?string $direction = null;
    public string $status = 'active';
    public string $notes = '';

    #[On('open-mining-labor-form')]
    public function open(?int $laborId = null, ?string $context = null): void
    {
        $this->context = $context;

        if ($laborId) {
            $labor = MiningLabor::findOrFail($laborId);

            $this->mode = 'edit';
            $this->laborId = $labor->id;
            $this->laborType = $labor->labor_type;
            $this->veinName = $labor->vein_name;
            $this->levelNumber = $labor->level_number;
            $this->direction = $labor->direction;
            $this->status = $labor->status;
            $this->notes = $labor->notes ?? '';
        } else {
            $this->mode = 'create';
            $this->reset(['laborId', 'veinName', 'levelNumber', 'direction', 'notes']);
            $this->laborType = 'tajo';
            $this->status = 'active';
        }

        $this->show = true;
    }

    public function getVeinSuggestionsProperty(): array
    {
        return MiningLabor::veinSuggestions();
    }

    private function rules(): array
    {
        return [
            'laborType' => ['required', Rule::in(array_keys(MiningLabor::PREFIXES))],
            'veinName' => ['required', 'string', 'max:50'],
            'levelNumber' => ['required', 'integer', 'min:1'],
            'direction' => ['nullable', 'in:este,oeste,norte,sur'],
            'status' => ['required', 'in:active,exhausted,paused'],
        ];
    }

    public function save(): void
    {
        $this->validate($this->rules());

        // Validación de duplicado (misma regla que la unique compuesta de BD,
        // pero con mensaje amigable en vez de una excepción SQL cruda)
        $duplicate = MiningLabor::query()
            ->where('labor_type', $this->laborType)
            ->where('level_number', $this->levelNumber)
            ->where('vein_name', $this->veinName)
            ->when($this->laborId, fn ($q) => $q->where('id', '!=', $this->laborId))
            ->exists();

        if ($duplicate) {
            Flux::toast('Ya existe una labor con este tipo, nivel y veta.', 'Error');
            return;
        }

        try {
            $payload = [
                'labor_type' => $this->laborType,
                'vein_name' => $this->veinName,
                'level_number' => $this->levelNumber,
                'direction' => $this->direction,
                'status' => $this->status,
                'notes' => $this->notes,
            ];

            if ($this->mode === 'edit') {
                $labor = MiningLabor::findOrFail($this->laborId);
                $labor->update($payload);
            } else {
                $labor = MiningLabor::create($payload);
            }

            $this->dispatch(
                'mining-labor-saved',
                laborId: $labor->id,
                code: $labor->code,
                context: $this->context,
            );

            Flux::toast($this->mode === 'edit' ? 'Labor actualizada correctamente.' : 'Labor registrada correctamente.');
            $this->show = false;
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
        return view('livewire.mining-labors.mining-labor-form');
    }
}