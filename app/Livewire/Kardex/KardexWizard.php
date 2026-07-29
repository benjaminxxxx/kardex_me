<?php

namespace App\Livewire\Kardex;

use App\Models\Kardex;
use App\Services\KardexService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class KardexWizard extends Component
{
    public int $step = 1;

    public ?int $productId = null;
    public ?string $productLabel = null;

    public string $year = '';
    public string $month = '';

    public bool $suggested = false;
    public string $openingQty = '0';
    public string $openingUnitCost = '0';

    public function mount(): void
    {
        $this->year = (string) now()->year;
        $this->month = (string) now()->month;
    }

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'kardex-product') {
            $this->productId = $id;
            $this->productLabel = $label;
            $this->suggested = false;
            $this->step = 2;
        }
    }

    /** true si el producto NUNCA tuvo un kardex (ni abierto ni cerrado) */
    public function getIsFirstKardexProperty(): bool
    {
        return ! Kardex::where('product_id', $this->productId)->exists();
    }

    public function getOpenKardexProperty(): ?Kardex
    {
        return Kardex::where('product_id', $this->productId)
            ->where('status', 'open')
            ->first();
    }

    public function getClosedMonthsProperty()
    {
        if (! $this->productId) return collect();

        return Kardex::where('product_id', $this->productId)
            ->where('year', $this->year)
            ->where('status', 'closed')
            ->orderBy('month')
            ->get();
    }

    public function getNextPeriodLabelProperty(): ?string
    {
        $ultimo = Kardex::where('product_id', $this->productId)
            ->where('status', 'closed')
            ->orderByDesc('year')->orderByDesc('month')
            ->first();

        if (! $ultimo) return null;

        $meses = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $nextMonth = $ultimo->month === 12 ? 1 : $ultimo->month + 1;
        $nextYear = $ultimo->month === 12 ? $ultimo->year + 1 : $ultimo->year;

        return "{$meses[$nextMonth]} {$nextYear}";
    }

    /** Sugiere el saldo inicial calculando desde stock_movements antes del periodo elegido */
    public function suggestOpeningBalance(KardexService $service): void
    {
        $this->validate([
            'year' => ['required', 'integer', 'min:2000'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $cutoff = \Carbon\Carbon::create((int) $this->year, (int) $this->month, 1)->startOfMonth();

        $resultado = $service->previewOpeningBalanceAsOf($this->productId, $cutoff);

        $this->openingQty = (string) $resultado['qty'];
        $this->openingUnitCost = (string) $resultado['unit_cost'];
        $this->suggested = true;
    }

    public function createFirstKardex(KardexService $service): void
    {
        $this->validate([
            'year' => ['required', 'integer', 'min:2000'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'openingQty' => ['required', 'numeric', 'min:0'],
            'openingUnitCost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $kardex = $service->openFirst(
                $this->productId,
                (int) $this->year,
                (int) $this->month,
                (float) $this->openingQty,
                (float) $this->openingUnitCost
            );

            $service->calculate($kardex);

            Flux::toast('Kardex inicial creado y calculado correctamente.');
            $this->redirect(route('kardex.show', $kardex), navigate: true);
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function createNextKardex(KardexService $service): void
    {
        try {
            $kardex = $service->openNext($this->productId, (int) $this->year);
            $service->calculate($kardex);

            Flux::toast('Kardex creado y calculado correctamente.');
            $this->redirect(route('kardex.show', $kardex), navigate: true);
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.kardex.kardex-wizard');
    }
}