<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Support\ExcelHelper;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

#[Title('Proveedores')]
class SupplierList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public string $sortBy = 'supplier_code';
    public string $sortDirection = 'asc';
    public bool $showTrashed = false;
    protected $listeners = [
        'supplier-saved' => '$refresh',
    ];

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
        $this->reset('search', 'status');
    }

    public function getSuppliersProperty()
    {
        return Supplier::query()
            ->with('person')
            ->when($this->showTrashed, fn($query) => $query->onlyTrashed())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('person', function ($p) {
                        $p->where('display_name', 'like', "%{$this->search}%")
                            ->orWhere('document_number', 'like', "%{$this->search}%");
                    })->orWhere('supplier_code', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn($query) => $query->where('status', $this->status))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }
    public function restoreSupplier(int $supplierId): void
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($supplierId);
        $supplier->restore();

        Flux::toast('Proveedor restaurado correctamente.');
    }

    public function viewDetails(int $supplierId): void
    {
        $this->dispatch('open-supplier-details', supplierId: $supplierId);
    }

    public function exportToExcel()
    {
        try {
            $spreadsheet = ExcelHelper::loadTemplate('rpt_lista_proveedores.xlsx');

            $hojaProveedor = $spreadsheet->getSheetByName('PROVEEDOR');
            $hojaDirecciones = $spreadsheet->getSheetByName('DIRECCIONES');
            $hojaCuentas = $spreadsheet->getSheetByName('CUENTAS_BANCARIAS');

            if (!$hojaProveedor || !$hojaDirecciones || !$hojaCuentas) {
                throw new Exception("La plantilla debe contener las hojas 'PROVEEDOR', 'DIRECCIONES' y 'CUENTAS_BANCARIAS'.");
            }

            $filaInicioProveedor = 5;
            $filaProveedor = $filaInicioProveedor;

            $filaInicioDirecciones = 5;
            $filaDireccion = $filaInicioDirecciones;

            $filaInicioCuentas = 5;
            $filaCuenta = $filaInicioCuentas;

            $tipoPersonaLabels = [
                'individual' => 'Persona Natural',
                'company' => 'Persona Jurídica',
            ];

            $estadoProveedorLabels = [
                'prospect' => 'Prospecto',
                'approved' => 'Homologado',
                'suspended' => 'Suspendido',
                'blacklisted' => 'Vetado',
            ];

            $tipoDireccionLabels = [
                'fiscal' => 'Fiscal',
                'laboratory' => 'Laboratorio',
                'field' => 'Campo',
                'warehouse' => 'Almacén',
                'other' => 'Otro',
            ];

            $tipoCuentaLabels = [
                'bank_account' => 'Cuenta bancaria',
                'digital_wallet' => 'Billetera digital',
            ];

            // Recorremos solo Suppliers (no todas las Personas), con sus relaciones
            Supplier::query()
                ->with([
                    'person',
                    'person.createdBy',
                    'person.updatedBy',
                    'branches',
                    'bankAccounts',
                    'createdBy',
                    'updatedBy',
                ])
                ->orderBy('id')
                ->chunkById(200, function ($proveedores) use ($hojaProveedor, &$filaProveedor, $hojaDirecciones, &$filaDireccion, $hojaCuentas, &$filaCuenta, $tipoPersonaLabels, $estadoProveedorLabels, $tipoDireccionLabels, $tipoCuentaLabels) {
                    foreach ($proveedores as $proveedor) {

                        $persona = $proveedor->person;

                        // Datos que se repiten en las 3 hojas para que sean auto-entendibles
                        $nombreProveedor = $persona->display_name;
                        $documento = "{$persona->document_type}: {$persona->document_number}";

                        $empleado = $persona->employee;
                        $usuario = $persona->user;

                        // ============ N° ============
                        $hojaProveedor->setCellValue("A{$filaProveedor}", $filaProveedor - 4); // correlativo empezando en 1
    
                        // ============ DATOS PERSONALES ============
                        $hojaProveedor->setCellValue("B{$filaProveedor}", $persona->code);
                        $hojaProveedor->setCellValue("C{$filaProveedor}", $tipoPersonaLabels[$persona->type] ?? $persona->type);
                        $hojaProveedor->setCellValue("D{$filaProveedor}", $persona->document_type);
                        $hojaProveedor->setCellValueExplicit(
                            "E{$filaProveedor}",
                            $persona->document_number,
                            DataType::TYPE_STRING
                        );
                        $hojaProveedor->setCellValue("F{$filaProveedor}", $persona->company_name ?? '');
                        $hojaProveedor->setCellValue("G{$filaProveedor}", $persona->legal_name ?? '');
                        $hojaProveedor->setCellValue("H{$filaProveedor}", $persona->names ?? '');
                        $hojaProveedor->setCellValue("I{$filaProveedor}", $persona->paternal_last_name ?? '');
                        $hojaProveedor->setCellValue("J{$filaProveedor}", $persona->maternal_last_name ?? '');

                        ExcelHelper::setFechaCell($hojaProveedor, "K{$filaProveedor}", $persona->birth_date);

                        $hojaProveedor->setCellValue("L{$filaProveedor}", $generoLabels[$persona->gender] ?? '');
                        $hojaProveedor->setCellValue("M{$filaProveedor}", $estadoCivilLabels[$persona->marital_status] ?? '');
                        $hojaProveedor->setCellValue("N{$filaProveedor}", $persona->mobile ?? '');
                        $hojaProveedor->setCellValue("O{$filaProveedor}", $persona->phone ?? '');
                        $hojaProveedor->setCellValue("P{$filaProveedor}", $persona->email ?? '');
                        $hojaProveedor->setCellValue("Q{$filaProveedor}", $persona->country ?? '');
                        $hojaProveedor->setCellValue("R{$filaProveedor}", $persona->state ?? '');
                        $hojaProveedor->setCellValue("S{$filaProveedor}", $persona->city ?? '');
                        $hojaProveedor->setCellValue("T{$filaProveedor}", $persona->district ?? '');
                        $hojaProveedor->setCellValue("U{$filaProveedor}", $persona->postal_code ?? '');
                        $hojaProveedor->setCellValue("V{$filaProveedor}", $persona->address ?? '');
                        $hojaProveedor->setCellValue("W{$filaProveedor}", $persona->notes ?? '');
                        $hojaProveedor->setCellValue("X{$filaProveedor}", $persona->is_active ? 'Activo' : 'Inactivo');

                        ExcelHelper::setFechaCell($hojaProveedor, "Y{$filaProveedor}", $persona->created_at, true);
                        ExcelHelper::setFechaCell($hojaProveedor, "Z{$filaProveedor}", $persona->updated_at, true);

                        $hojaProveedor->setCellValue("AA{$filaProveedor}", $persona->created_by_name ?? '');
                        $hojaProveedor->setCellValue("AB{$filaProveedor}", $persona->updated_by_name ?? '');

                        // ============ DATOS DE ACCESO (opcional) ============
                        $hojaProveedor->setCellValue("AC{$filaProveedor}", $usuario->email ?? '');
                        $hojaProveedor->setCellValue("AD{$filaProveedor}", $usuario?->roles->first()?->name ?? '');

                        if ($usuario) {
                            ExcelHelper::setFechaCell($hojaProveedor, "AE{$filaProveedor}", $usuario->created_at, true);
                            ExcelHelper::setFechaCell($hojaProveedor, "AF{$filaProveedor}", $usuario->updated_at, true);
                        }

                        $hojaProveedor->setCellValue("AG{$filaProveedor}", $usuario->created_by_name ?? '');
                        $hojaProveedor->setCellValue("AH{$filaProveedor}", $usuario->updated_by_name ?? '');

                        // ============ DATOS DE PROVEEDOR ============
                        $hojaProveedor->setCellValue("AI{$filaProveedor}", $proveedor->supplier_code ?? '');
                        $hojaProveedor->setCellValue("AJ{$filaProveedor}", $estadoProveedorLabels[$proveedor->status] ?? '');
                        $hojaProveedor->setCellValue("AK{$filaProveedor}", $proveedor->notes ?? '');

                        if ($usuario) {
                            ExcelHelper::setFechaCell($hojaProveedor, "AL{$filaProveedor}", $proveedor->created_at, true);
                            ExcelHelper::setFechaCell($hojaProveedor, "AM{$filaProveedor}", $proveedor->updated_at, true);
                        }

                        $hojaProveedor->setCellValue("AN{$filaProveedor}", $proveedor->created_by_name ?? '');
                        $hojaProveedor->setCellValue("AO{$filaProveedor}", $proveedor->updated_by_name ?? '');

                        $filaProveedor++;

                        // ============ HOJA: DIRECCIONES ============
                        foreach ($proveedor->branches as $branch) {
                            $hojaDirecciones->setCellValue("A{$filaDireccion}", $filaDireccion - 4);
                            $hojaDirecciones->setCellValue("B{$filaDireccion}", $proveedor->supplier_code);
                            $hojaDirecciones->setCellValue("C{$filaDireccion}", $nombreProveedor);
                            $hojaDirecciones->setCellValue("D{$filaDireccion}", $documento);
                            $hojaDirecciones->setCellValue("E{$filaDireccion}", $tipoDireccionLabels[$branch->type] ?? $branch->type);
                            $hojaDirecciones->setCellValue("F{$filaDireccion}", $branch->name);
                            $hojaDirecciones->setCellValue("G{$filaDireccion}", $branch->is_main ? 'Sí' : 'No');
                            $hojaDirecciones->setCellValue("H{$filaDireccion}", $branch->country ?? '');
                            $hojaDirecciones->setCellValue("I{$filaDireccion}", $branch->state ?? '');
                            $hojaDirecciones->setCellValue("J{$filaDireccion}", $branch->city ?? '');
                            $hojaDirecciones->setCellValue("K{$filaDireccion}", $branch->address ?? '');
                            $hojaDirecciones->setCellValue("L{$filaDireccion}", $branch->phone ?? '');
                            $hojaDirecciones->setCellValue("M{$filaDireccion}", $branch->email ?? '');

                            $filaDireccion++;
                        }

                        // ============ HOJA: CUENTAS_BANCARIAS ============
                        foreach ($proveedor->bankAccounts as $account) {
                            $hojaCuentas->setCellValue("A{$filaCuenta}", $filaCuenta - 4);
                            $hojaCuentas->setCellValue("B{$filaCuenta}", $proveedor->supplier_code);
                            $hojaCuentas->setCellValue("C{$filaCuenta}", $nombreProveedor);
                            $hojaCuentas->setCellValue("D{$filaCuenta}", $documento);
                            $hojaCuentas->setCellValue("E{$filaCuenta}", $tipoCuentaLabels[$account->type] ?? $account->type);
                            $hojaCuentas->setCellValue("F{$filaCuenta}", $account->bank_name ?? '');
                            $hojaCuentas->setCellValue("G{$filaCuenta}", $account->account_number ?? '');
                            $hojaCuentas->setCellValue("H{$filaCuenta}", $account->cci ?? '');
                            $hojaCuentas->setCellValue("I{$filaCuenta}", $account->currency ?? '');
                            $hojaCuentas->setCellValue("J{$filaCuenta}", $account->wallet_provider ?? '');
                            $hojaCuentas->setCellValue("K{$filaCuenta}", $account->wallet_phone ?? '');
                            $hojaCuentas->setCellValue("L{$filaCuenta}", $account->account_holder_name ?? '');
                            $hojaCuentas->setCellValue("M{$filaCuenta}", $account->is_main ? 'Sí' : 'No');

                            $filaCuenta++;
                        }
                    }
                });

            // Bordes por hoja, cada una con su propio rango final
            ExcelHelper::aplicarBordesRango($hojaProveedor, "A{$filaInicioProveedor}:S" . max($filaProveedor - 1, $filaInicioProveedor));
            ExcelHelper::aplicarBordesRango($hojaDirecciones, "A{$filaInicioDirecciones}:M" . max($filaDireccion - 1, $filaInicioDirecciones));
            ExcelHelper::aplicarBordesRango($hojaCuentas, "A{$filaInicioCuentas}:M" . max($filaCuenta - 1, $filaInicioCuentas));

            return ExcelHelper::download($spreadsheet, 'REPORTE_PROVEEDORES.xlsx');

        } catch (\Throwable $th) {
            Flux::toast('Error al exportar a Excel: ' . $th->getMessage(), 'error');
        }
    }

    public function editPersonalInfo(int $personId): void
    {
        $this->dispatch('open-person-editor', personId: $personId);
    }

    public function editSupplierInfo(int $supplierId): void
    {
        $this->dispatch('open-supplier-editor', supplierId: $supplierId, tab: 'general');
    }

    public function manageBranches(int $supplierId): void
    {
        $this->dispatch('open-supplier-editor', supplierId: $supplierId, tab: 'branches');
    }

    public function manageBankAccounts(int $supplierId): void
    {
        $this->dispatch('open-supplier-editor', supplierId: $supplierId, tab: 'bank-accounts');
    }

    public function manageAccess(int $personId): void
    {
        $this->dispatch('open-access-manager', personId: $personId);
    }
    public function deactivateSupplier(int $supplierId): void
    {
        $supplier = Supplier::with('person.user')->findOrFail($supplierId);

        try {
            DB::transaction(function () use ($supplier) {
                // 1. Revocar acceso, sin eliminar el User
                if ($supplier->person->user) {
                    $supplier->person->user->delete();

                    // Opcional pero recomendable: forzar cierre de sesión
                    // eliminando sus sesiones activas de la tabla `sessions`.
                    DB::table('sessions')->where('user_id', $supplier->person->user->id)->delete();
                }

                // 2. Eliminar (soft delete) el registro de proveedor
                $supplier->delete();

                // 3. Person NO se toca — sigue existiendo intacta
            });

            $this->dispatch('supplier-saved'); // refresca el listado
            Flux::toast('Proveedor dado de baja. Su acceso al sistema fue revocado.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }
    public function render()
    {
        return view('livewire.suppliers.supplier-list', [
            'suppliers' => $this->suppliers,
        ]);
    }
}