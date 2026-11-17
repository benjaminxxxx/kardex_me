<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Services\SupplierContactsService;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class SupplierEditor extends Component
{
    public bool $show = false;
    public ?int $supplierId = null;
    public ?string $displayName = null;
    public string $activeTab = 'general';

    // ===== Tab: General / homologación =====
    public string $status = 'prospect';
    public string $notes = '';

    // ===== Tab: Direcciones =====
    public array $branches = [];

    // ===== Tab: Cuentas bancarias =====
    public array $bankAccounts = [];

    #[On('open-supplier-editor')]
    public function open(int $supplierId, string $tab = 'general'): void
    {
        $supplier = Supplier::with(['person', 'branches', 'bankAccounts'])->findOrFail($supplierId);

        $this->supplierId = $supplier->id;
        $this->displayName = $supplier->person->display_name;
        $this->activeTab = $tab;

        $this->status = $supplier->status;
        $this->notes = $supplier->notes ?? '';

        $this->branches = $supplier->branches->map(fn ($branch) => [
            'id' => $branch->id,
            'type' => $branch->type,
            'name' => $branch->name,
            'is_main' => $branch->is_main,
            'country' => $branch->country ?? '',
            'state' => $branch->state ?? '',
            'city' => $branch->city ?? '',
            'address' => $branch->address ?? '',
            'phone' => $branch->phone ?? '',
            'email' => $branch->email ?? '',
        ])->toArray();

        if (empty($this->branches)) {
            $this->branches = [$this->emptyBranch()];
        }

        $this->bankAccounts = $supplier->bankAccounts->map(fn ($account) => [
            'id' => $account->id,
            'type' => $account->type,
            'bank_name' => $account->bank_name ?? '',
            'account_number' => $account->account_number ?? '',
            'cci' => $account->cci ?? '',
            'currency' => $account->currency ?? 'PEN',
            'wallet_provider' => $account->wallet_provider ?? '',
            'wallet_phone' => $account->wallet_phone ?? '',
            'account_holder_name' => $account->account_holder_name ?? '',
            'is_main' => $account->is_main,
        ])->toArray();

        if (empty($this->bankAccounts)) {
            $this->bankAccounts = [$this->emptyBankAccount()];
        }

        $this->show = true;
    }

    private function emptyBranch(): array
    {
        return [
            'id' => null,
            'type' => 'fiscal',
            'name' => '',
            'is_main' => true,
            'country' => 'Perú',
            'state' => '',
            'city' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
        ];
    }

    private function emptyBankAccount(): array
    {
        return [
            'id' => null,
            'type' => 'bank_account',
            'bank_name' => '',
            'account_number' => '',
            'cci' => '',
            'currency' => 'PEN',
            'wallet_provider' => '',
            'wallet_phone' => '',
            'account_holder_name' => '',
            'is_main' => false,
        ];
    }

    // ===== Direcciones =====

    public function addBranch(): void
    {
        $this->branches[] = $this->emptyBranch();
    }

    public function removeBranch(int $index): void
    {
        unset($this->branches[$index]);
        $this->branches = array_values($this->branches);
    }

    // ===== Cuentas bancarias =====

    public function addBankAccount(): void
    {
        $this->bankAccounts[] = $this->emptyBankAccount();
    }

    public function removeBankAccount(int $index): void
    {
        unset($this->bankAccounts[$index]);
        $this->bankAccounts = array_values($this->bankAccounts);
    }

    public function save(): void
    {
        $this->validate([
            'status' => ['required', Rule::in(['prospect', 'approved', 'suspended', 'blacklisted'])],
            'branches.*.type' => ['required', 'in:fiscal,laboratory,field,warehouse,other'],
            'branches.*.name' => ['required', 'string', 'max:255'],
            'bankAccounts.*.type' => ['required', 'in:bank_account,digital_wallet'],
        ]);

        try {
            DB::transaction(function () {
                $supplier = Supplier::findOrFail($this->supplierId);

                $supplier->update([
                    'status' => $this->status,
                    'notes' => $this->notes,
                ]);

                // Actualiza en lugar de borrar y recrear; los cambios van a Auditoría
                app(SupplierContactsService::class)->sync($supplier, $this->branches, $this->bankAccounts);
            });

            $this->dispatch('supplier-saved');
            $this->show = false;
            Flux::toast('Proveedor actualizado correctamente.');
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
        return view('livewire.suppliers.supplier-editor');
    }
}