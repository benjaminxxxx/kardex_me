<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\Purchase\ExportToExcelService;

#[Title('Compras')]
class PurchaseList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $documentType = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $supplierId = null;
    public ?string $supplierLabel = null;

    public string $sortBy = 'document_date';
    public string $sortDirection = 'desc';
    public bool $showTrashed = false;

    protected $listeners = ['purchase-saved' => '$refresh'];

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'purchase-filter-supplier') {
            $this->supplierId = $id;
            $this->supplierLabel = $label;
            $this->resetPage();
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'purchase-filter-supplier') {
            $this->supplierId = null;
            $this->supplierLabel = null;
            $this->resetPage();
        }
    }

    public function sort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'documentType', 'dateFrom', 'dateTo', 'supplierId', 'supplierLabel');
    }

    public function getPurchasesProperty()
    {
        return Purchase::query()
            ->with(['supplier.person', 'warehouse'])
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('document_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier.person', fn($p) => $p->where('display_name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->documentType, fn($q) => $q->where('document_type', $this->documentType))
            ->when($this->supplierId, fn($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('document_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('document_date', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function deletePurchase(int $purchaseId): void
    {
        Purchase::findOrFail($purchaseId)->delete();
        Flux::toast('Compra eliminada. Puedes restaurarla desde "Ver eliminadas". El stock generado permanece hasta que se elimine definitivamente.');
    }

    public function restorePurchase(int $purchaseId): void
    {
        Purchase::onlyTrashed()->findOrFail($purchaseId)->restore();
        Flux::toast('Compra restaurada correctamente.');
    }

    public function exportToExcel(ExportToExcelService $service)
    {
        try {
            return $service->execute([
                'search' => $this->search,
                'documentType' => $this->documentType,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'supplierId' => $this->supplierId,
                'supplierLabel' => $this->supplierLabel,
                'showTrashed' => $this->showTrashed,
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
            ]);
        } catch (\Throwable $e) {
            Flux::toast('Error al exportar a Excel: ' . $e->getMessage(), 'error');
        }
    }
    public function render()
    {
        return view('livewire.purchases.purchase-list', [
            'purchases' => $this->purchases,
        ]);
    }
}