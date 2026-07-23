<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\On;
use Livewire\Component;

class SupplierDetailsViewer extends Component
{
    public bool $show = false;
    public ?Supplier $supplier = null;

    #[On('open-supplier-details')]
    public function open(int $supplierId): void
    {
        $this->supplier = Supplier::with([
            'person',
            'person.user',
            'person.user.roles',
            'person.createdBy',
            'person.updatedBy',
            'person.user.createdBy',
            'person.user.updatedBy',
            'branches',
            'bankAccounts',
            'createdBy',
            'updatedBy',
        ])->findOrFail($supplierId);

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->supplier = null;
    }

    public function render()
    {
        return view('livewire.suppliers.supplier-details-viewer');
    }
}