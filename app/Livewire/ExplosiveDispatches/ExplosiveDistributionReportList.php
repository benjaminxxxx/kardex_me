<?php

namespace App\Livewire\ExplosiveDispatches;

use App\Models\ExplosiveFieldDispatch;
use App\Models\ExplosiveFieldDistribution;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Reporte de distribución de explosivos')]
class ExplosiveDistributionReportList extends Component
{
    use WithPagination;

    #[Url]
    public string $year = '';

    #[Url]
    public string $month = '';

    #[Url]
    public string $day = ''; // fecha exacta, formato Y-m-d

    public ?int $drillerEmployeeId = null;
    public ?string $drillerEmployeeLabel = null;

    public ?int $miningLaborId = null;
    public ?string $miningLaborLabel = null;

    protected $listeners = ['distribution-saved' => '$refresh'];

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'report-driller') {
            $this->drillerEmployeeId = $id;
            $this->drillerEmployeeLabel = $label;
            $this->resetPage();
        }

        if ($context === 'report-labor') {
            $this->miningLaborId = $id;
            $this->miningLaborLabel = $label;
            $this->resetPage();
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'report-driller') {
            $this->drillerEmployeeId = null;
            $this->drillerEmployeeLabel = null;
        }

        if ($context === 'report-labor') {
            $this->miningLaborId = null;
            $this->miningLaborLabel = null;
        }

        $this->resetPage();
    }

    public function updatedYear(): void { $this->resetPage(); }
    public function updatedMonth(): void { $this->resetPage(); }
    public function updatedDay(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset('year', 'month', 'day', 'drillerEmployeeId', 'drillerEmployeeLabel', 'miningLaborId', 'miningLaborLabel');
    }

    public function getAvailableYearsProperty()
    {
        return ExplosiveFieldDispatch::selectRaw('DISTINCT YEAR(dispatch_date) as y')
            ->orderByDesc('y')
            ->pluck('y');
    }

    public function getDistributionsProperty()
    {
        return ExplosiveFieldDistribution::query()
            ->with(['dispatch', 'miningLabor', 'driller.person'])
            ->whereHas('dispatch', function ($q) {
                $q->when($this->year, fn ($qq) => $qq->whereYear('dispatch_date', $this->year))
                  ->when($this->month, fn ($qq) => $qq->whereMonth('dispatch_date', $this->month))
                  ->when($this->day, fn ($qq) => $qq->whereDate('dispatch_date', $this->day));
            })
            ->when($this->drillerEmployeeId, fn ($q) => $q->where('driller_employee_id', $this->drillerEmployeeId))
            ->when($this->miningLaborId, fn ($q) => $q->where('mining_labor_id', $this->miningLaborId))
            ->join('explosive_field_dispatches', 'explosive_field_dispatches.id', '=', 'explosive_field_distributions.dispatch_id')
            ->orderByDesc('explosive_field_dispatches.dispatch_date')
            ->select('explosive_field_distributions.*')
            ->paginate(15);
    }

    public function exportToExcel()
    {
        // Pendiente
    }

    public function render()
    {
        return view('livewire.explosive-dispatches.explosive-distribution-report-list', [
            'distributions' => $this->distributions,
        ]);
    }
}