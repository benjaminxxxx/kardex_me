<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Models\SupplierBranch;
use App\Models\SupplierBankAccount;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Crear proveedores')]
class SupplierWizard extends Component
{
    public int $step = 1;

    // ===== Paso 1: Persona =====
    public ?int $personId = null;
    public ?string $personDisplayName = null;
    public ?string $personDocumentNumber = null;

    // ===== Paso 2: Homologación =====
    public string $status = 'prospect';
    public string $notes = '';

    // ===== Paso 3: Direcciones (supplier_branches) =====
    public array $branches = [];

    // ===== Paso 4: Cuentas bancarias / billeteras =====
    public array $bankAccounts = [];
    public ?int $restoringSupplierId = null;

    public function mount(): void
    {
        // Al menos una dirección fiscal por defecto, para no obligar al usuario
        // a acordarse de agregarla manualmente.
        $this->branches = [
            [
                'type' => 'fiscal',
                'name' => '',
                'is_main' => true,
                'country' => 'Perú',
                'state' => '',
                'city' => '',
                'address' => '',
                'phone' => '',
                'email' => '',
            ],
        ];

        $this->bankAccounts = [
            $this->emptyBankAccount(),
        ];
    }

    private function emptyBankAccount(): array
    {
        return [
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

    #[On('person-selected')]
    public function handlePersonSelected(int $personId, string $displayName, string $documentNumber, ?string $context = null): void
    {
        if ($context !== 'supplier-wizard') {
            return;
        }

        $existing = Supplier::withTrashed()->where('person_id', $personId)->first();

        if ($existing && !$existing->trashed()) {
            // Ya es proveedor activo, no se puede duplicar
            Flux::toast('Esta persona/empresa ya está registrada como proveedor.', 'Error');
            $this->redirect(route('suppliers.index'), navigate: true);
            return;
        }

        if ($existing && $existing->trashed()) {
            // Estaba dado de baja: vamos a reactivarlo, no a crear uno nuevo
            $this->restoringSupplierId = $existing->id;
            Flux::toast('Esta persona o empresa ya fue proveedor. Se reactivará el registro existente.', 'info');
        } else {
            $this->restoringSupplierId = null;
        }


        $this->personId = $personId;
        $this->personDisplayName = $displayName;
        $this->personDocumentNumber = $documentNumber;
        $this->step = 2;
    }

    public function changePerson(): void
    {
        $this->reset(['personId', 'personDisplayName', 'personDocumentNumber']);
        $this->step = 1;
        $this->dispatch('open-person-selector', context: 'supplier-wizard');
    }

    public function goToStep(int $step): void
    {
        // Evita saltar pasos sin haber completado el anterior
        if ($step > 1 && !$this->personId) {
            return;
        }

        $this->step = $step;
    }

    public function nextStep(): void
    {
        if ($this->step === 2) {
            $this->validate([
                'status' => ['required', 'in:prospect,approved,suspended,blacklisted'],
            ]);
        }

        if ($this->step === 3) {
            $this->validate([
                'branches.*.type' => ['required', 'in:fiscal,laboratory,field,warehouse,other'],
                'branches.*.name' => ['required', 'string', 'max:255'],
            ]);
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step--;
    }

    // ===== Manejo de direcciones =====

    public function addBranch(): void
    {
        $this->branches[] = [
            'type' => 'field',
            'name' => '',
            'is_main' => false,
            'country' => 'Perú',
            'state' => '',
            'city' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
        ];
    }

    public function removeBranch(int $index): void
    {
        unset($this->branches[$index]);
        $this->branches = array_values($this->branches);
    }

    // ===== Manejo de cuentas bancarias =====

    public function addBankAccount(): void
    {
        $this->bankAccounts[] = $this->emptyBankAccount();
    }

    public function removeBankAccount(int $index): void
    {
        unset($this->bankAccounts[$index]);
        $this->bankAccounts = array_values($this->bankAccounts);
    }

    // ===== Guardado final =====

    public function save(): void
    {
        $this->validate([
            'status' => ['required', 'in:prospect,approved,suspended,blacklisted'],
            'branches.*.type' => ['required', 'in:fiscal,laboratory,field,warehouse,other'],
            'branches.*.name' => ['required', 'string', 'max:255'],
            'bankAccounts.*.type' => ['required', 'in:bank_account,digital_wallet'],
        ]);

        if (!$this->personId) {
            Flux::toast('Debe seleccionar una persona antes de guardar.', 'Error');
            $this->step = 1;
            return;
        }

        try {
            DB::transaction(function () {

                if ($this->restoringSupplierId) {
                    // ===== Reactivar proveedor existente =====
                    $supplier = Supplier::withTrashed()->findOrFail($this->restoringSupplierId);
                    $supplier->restore();
                    $supplier->update([
                        'status' => $this->status,
                        'notes' => $this->notes,
                        // supplier_code se conserva, no se genera uno nuevo
                    ]);

                    // Limpiamos direcciones/cuentas anteriores para reemplazarlas
                    // por lo que el usuario acaba de llenar en el wizard
                    $supplier->branches()->delete();
                    $supplier->bankAccounts()->delete();
                } else {
                    // ===== Alta nueva =====
                    $supplier = Supplier::create([
                        'person_id' => $this->personId,
                        'supplier_code' => 'PROV-' . str_pad((string) (Supplier::max('id') + 1), 6, '0', STR_PAD_LEFT),
                        'status' => $this->status,
                        'notes' => $this->notes,
                    ]);
                }

                foreach ($this->branches as $branch) {
                    SupplierBranch::create([
                        'supplier_id' => $supplier->id,
                        ...$branch,
                    ]);
                }

                foreach ($this->bankAccounts as $account) {
                    $hasBankData = filled($account['account_number']) || filled($account['bank_name']);
                    $hasWalletData = filled($account['wallet_phone']);

                    if (!$hasBankData && !$hasWalletData) {
                        continue;
                    }

                    SupplierBankAccount::create([
                        'supplier_id' => $supplier->id,
                        ...$account,
                    ]);
                }

                $this->dispatch('supplier-saved');
            });

            Flux::toast(
                $this->restoringSupplierId
                ? 'Proveedor reactivado correctamente.'
                : 'Proveedor registrado correctamente.'
            );
            $this->redirect(route('suppliers.index'), navigate: true);
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.suppliers.supplier-wizard');
    }
}