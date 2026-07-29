<?php

namespace App\Livewire\Kardex;

use App\Models\Kardex;
use App\Models\KardexMovement;
use App\Services\KardexService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

class KardexShow extends Component
{
    public Kardex $kardex;
    public bool $showOpeningEditor = false;
    public string $editOpeningQty = '';
    public string $editOpeningUnitCost = '';

    public function mount(Kardex $kardex): void
    {
        $this->kardex = $kardex->load([
            'product.unit',
            'movements' => function ($q) {
                $q->orderBy('movement_date')->orderBy('id');
            }
        ]);
    }

    public function openOpeningEditor(): void
    {
        $this->editOpeningQty = (string) $this->kardex->opening_qty;
        $this->editOpeningUnitCost = (string) $this->kardex->opening_unit_cost;
        $this->showOpeningEditor = true;
    }

    public function pullFromPreviousMonth(KardexService $service): void
    {
        $anterior = $service->previousMonthClosingBalance($this->kardex);

        if (!$anterior) {
            Flux::toast('No hay un kardex anterior de este producto para extraer el saldo.', 'Error');
            return;
        }

        $this->editOpeningQty = (string) $anterior['qty'];
        $this->editOpeningUnitCost = (string) $anterior['unit_cost'];

        Flux::toast("Saldo extraído del periodo {$anterior['period_label']}. Puedes ajustarlo antes de guardar.");
    }

    public function saveOpeningBalance(KardexService $service): void
    {
        $this->validate([
            'editOpeningQty' => ['required', 'numeric', 'min:0'],
            'editOpeningUnitCost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $service->updateOpeningBalance(
                $this->kardex,
                (float) $this->editOpeningQty,
                (float) $this->editOpeningUnitCost
            );

            $this->kardex->refresh()->load('movements');
            $this->showOpeningEditor = false;

            Flux::toast('Saldo inicial actualizado y kardex recalculado.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    #[\Livewire\Attributes\Computed]
    public function pageTitle(): string
    {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return "Kardex {$this->kardex->product->name} - {$meses[$this->kardex->month]} {$this->kardex->year}";
    }

    public function recalculate(KardexService $service): void
    {
        try {
            $service->calculate($this->kardex);
            $this->kardex->refresh()->load('movements');

            Flux::toast('Kardex recalculado correctamente.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function close(KardexService $service): void
    {
        try {
            $service->close($this->kardex);
            $this->kardex->refresh();

            Flux::toast('Kardex cerrado. El saldo final quedó fijado como saldo inicial del siguiente mes.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }
    
    public function render()
    {
        return view('livewire.kardex.kardex-show');
    }
}