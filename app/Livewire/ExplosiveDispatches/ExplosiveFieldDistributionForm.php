<?php

namespace App\Livewire\ExplosiveDispatches;

use App\Models\ExplosiveFieldDispatch;
use App\Models\MiningLabor;
use App\Services\ExplosiveFieldDispatchService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class ExplosiveFieldDistributionForm extends Component
{
    public ExplosiveFieldDispatch $dispatch;
    public array $rows = [];
    public bool $showConfirmation = false;

    private array $columns = [
        'fulminante_qty' => 'Fulminante (u)',
        'emulnor_qty' => 'Dinamita (u)',
        'mecha_lenta_qty' => 'Mecha Lenta (m)',
        'guia_qty' => 'Guía (m)',
        'guia_aux_qty' => 'Guía Aux (u)',
        'anfo_qty' => 'ANFO (kg)',
    ];

    public function mount(ExplosiveFieldDispatch $dispatch): void
    {
        $this->dispatch = $dispatch;

        $existentes = $dispatch->distributions()->with(['miningLabor', 'driller.person'])->get();

        if ($existentes->isEmpty()) {
            $this->addRow();
        } else {
            foreach ($existentes as $dist) {
                $this->rows[] = $this->rowFromModel($dist);
            }
        }
    }

    private function rowFromModel($dist): array
    {
        return [
            'id' => $dist->id,
            'labor_id' => $dist->mining_labor_id,
            'labor_label' => $dist->miningLabor->code,
            'labor_type' => $dist->miningLabor->labor_type,
            'driller_id' => $dist->driller_employee_id,
            'driller_label' => $dist->driller->person->display_name,
            'drill_depth_feet' => (string) $dist->drill_depth_feet,
            'guide_length_feet' => (string) ($dist->guide_length_feet ?? 5),
            'fulminante_qty' => (string) $dist->fulminante_qty,
            'emulnor_qty' => (string) $dist->emulnor_qty,
            'mecha_lenta_qty' => (string) $dist->mecha_lenta_qty,
            'guia_qty' => (string) $dist->guia_qty,
            'guia_aux_qty' => (string) $dist->guia_aux_qty,
            'anfo_qty' => (string) $dist->anfo_qty,
        ];
    }

    private function emptyRow(): array
    {
        return [
            'id' => null,
            'labor_id' => null,
            'labor_label' => null,
            'labor_type' => null,
            'driller_id' => null,
            'driller_label' => null,
            'drill_depth_feet' => '',
            'guide_length_feet' => '5',
            'fulminante_qty' => '',
            'emulnor_qty' => '',
            'mecha_lenta_qty' => '',
            'guia_qty' => '',
            'guia_aux_qty' => '',
            'anfo_qty' => '',
        ];
    }

    public function addRow(): void
    {
        $this->rows[] = $this->emptyRow();
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if (! preg_match('/^row-(\d+)-(labor|driller)$/', $context, $m)) return;

        $index = (int) $m[1];
        $field = $m[2];

        if (! isset($this->rows[$index])) return;

        $this->rows[$index]["{$field}_id"] = $id;
        $this->rows[$index]["{$field}_label"] = $label;

        if ($field === 'labor') {
            $this->rows[$index]['labor_type'] = MiningLabor::find($id)?->labor_type;
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if (! preg_match('/^row-(\d+)-(labor|driller)$/', $context, $m)) return;

        $index = (int) $m[1];
        $field = $m[2];

        if (! isset($this->rows[$index])) return;

        $this->rows[$index]["{$field}_id"] = null;
        $this->rows[$index]["{$field}_label"] = null;

        if ($field === 'labor') {
            $this->rows[$index]['labor_type'] = null;
        }
    }

    /** Subtotales en vivo: solicitado, distribuido (filas actuales), remanente */
    public function getTotalsProperty(): array
    {
        $totals = [];

        foreach ($this->columns as $column => $label) {
            $solicitado = (float) $this->dispatch->$column;
            $distribuido = collect($this->rows)->sum(fn ($r) => (float) ($r[$column] ?: 0));

            $totals[$column] = [
                'label' => $label,
                'requested' => $solicitado,
                'distributed' => $distribuido,
                'remaining' => $solicitado - $distribuido,
            ];
        }

        return $totals;
    }

    public function reviewBeforeSave(): void
    {
        foreach ($this->rows as $row) {
            if (! $row['labor_id'] || ! $row['driller_id']) {
                Flux::toast('Cada fila necesita labor y perforista seleccionados.', 'Error');
                return;
            }
        }

        $this->showConfirmation = true;
    }

    public function confirmAndSave(ExplosiveFieldDispatchService $service): void
    {
        try {
            $service->saveDistribution($this->dispatch, $this->rows);

            $this->showConfirmation = false;
            Flux::toast('Distribución registrada correctamente.');
            $this->redirect(route('explosive-dispatches.index'), navigate: true);
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.explosive-dispatches.explosive-field-distribution-form', [
            'columns' => $this->columns,
        ]);
    }
}