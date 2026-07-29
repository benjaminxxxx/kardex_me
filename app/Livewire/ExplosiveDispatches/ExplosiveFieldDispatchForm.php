<?php

namespace App\Livewire\ExplosiveDispatches;

use App\Constants\Permisos;
use App\Models\CompanySetting;
use App\Models\ExplosiveRole;
use App\Models\Employee;
use App\Models\Product;
use App\Services\ExplosiveFieldDispatchService;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Component;

class ExplosiveFieldDispatchForm extends Component
{
    public string $dispatchDate = '';
    public string $shift = 'day';
    public ?int $dispatchedByEmployeeId = null;
    public ?int $requestedByEmployeeId = null;
    public string $notes = '';
    public ?int $warehouseId = null;

    // clave = explosive_role_id, valor = cantidad (string, casilla en blanco por defecto)
    public array $quantities = [];
    public array $selectedProducts = [];
    public bool $showConfirmation = false;
    public function mount(): void
    {
        $this->dispatchDate = now()->format('Y-m-d');
        $this->dispatchedByEmployeeId = $this->dispatchedByEmployee?->id;
        $this->warehouseId = CompanySetting::current()->mine_dispatch_warehouse_id;

        foreach ($this->roles as $role) {
            $this->quantities[$role->code] = '';

            $productos = $this->productsForRole($role->id);
            // Por defecto, el primer producto activo con ese rol (orden por id/created_at)
            $this->selectedProducts[$role->code] = $productos->first()?->id;
        }
    }
    public function getDispatchedByEmployeeProperty(): ?Employee
    {
        return Employee::where('person_id', auth()->user()->person_id)->first();
    }
    public function getEligibleSupervisorsProperty()
    {
        return Employee::with('person')
            ->whereHas('person.user', function ($q) {
                $q->permission(Permisos::EXPLOSIVOS_DISTRIBUIR);
            })
            ->where('status', 'active')
            ->get();
    }
    public function getRolesProperty()
    {
        return ExplosiveRole::where('is_active', true)->orderBy('sort_order')->get();
    }
    private function productsForRole(int $roleId)
    {
        return Product::where('explosive_role_id', $roleId)->where('is_active', true)->orderBy('id')->get();
    }
    /**
     * Por cada rol, lista de productos con su stock en el almacén configurado.
     * Estructura: ['detonator' => [['id'=>, 'name'=>, 'stock'=>], ...], ...]
     */
    public function getProductOptionsByRoleProperty(): array
    {
        $result = [];

        foreach ($this->roles as $role) {
            $productos = $this->productsForRole($role->id)->map(function ($product) {
                $stock = $this->warehouseId
                    ? StockService::available($product->id, $this->warehouseId)
                    : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->brand ? "{$product->name} ({$product->brand})" : $product->name,
                    'stock' => $stock,
                ];
            });

            $result[$role->code] = $productos;
        }

        return $result;
    }
    public function reviewBeforeSave(): void
    {
        $this->validate([
            'dispatchDate' => ['required', 'date'],
            'requestedByEmployeeId' => ['required', 'exists:employees,id'],
        ]);

        if (!$this->warehouseId) {
            Flux::toast('No hay un almacén configurado para salida a mina. Configúralo primero.', 'Error');
            return;
        }

        $tieneAlgunaCantidad = collect($this->quantities)->filter(fn($q) => filled($q) && (float) $q > 0)->isNotEmpty();

        if (!$tieneAlgunaCantidad) {
            Flux::toast('Debes ingresar al menos una cantidad.', 'Error');
            return;
        }

        // Validar producto elegido y stock suficiente por cada rol con cantidad
        foreach ($this->roles as $role) {
            $qty = (float) ($this->quantities[$role->code] ?? 0);
            if ($qty <= 0)
                continue;

            $productId = $this->selectedProducts[$role->code] ?? null;

            if (!$productId) {
                Flux::toast("No hay producto disponible para '{$role->name}'.", 'Error');
                return;
            }

            $stockDisponible = StockService::available($productId, $this->warehouseId);

            if ($qty > $stockDisponible) {
                Flux::toast("Stock insuficiente de '{$role->name}'. Disponible: {$stockDisponible}, solicitado: {$qty}.", 'Error');
                return;
            }
        }

        $this->showConfirmation = true;
    }


    public function getEmployeesProperty()
    {
        return Employee::with('person')->where('status', 'active')->get();
    }


    public function confirmAndSave(ExplosiveFieldDispatchService $service): void
    {

        if (!$this->dispatchedByEmployeeId) {
            Flux::toast('Tu usuario no está vinculado a un registro de empleado.', 'Error');
            return;
        }

        $this->validate([
            'dispatchDate' => ['required', 'date'],
            'shift' => ['required', 'in:day,night'],
            'requestedByEmployeeId' => ['required', 'exists:employees,id'],
        ]);

        $tieneAlgunaCantidad = collect($this->quantities)->filter(fn($q) => filled($q) && (float) $q > 0)->isNotEmpty();

        if (!$tieneAlgunaCantidad) {
            Flux::toast('Debes ingresar al menos una cantidad.', 'Error');
            return;
        }

        try {
            $service->create([
                'dispatch_date' => $this->dispatchDate,
                'shift' => $this->shift,
                'dispatched_by_employee_id' => $this->dispatchedByEmployeeId,
                'requested_by_employee_id' => $this->requestedByEmployeeId,
                'warehouse_id' => $this->warehouseId,
                'notes' => $this->notes,
            ], $this->quantities, $this->selectedProducts);

            Flux::toast('Despacho registrado correctamente.');
            $this->redirect(route('explosive-dispatches.index'), navigate: true);
            $this->showConfirmation = false;
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.explosive-dispatches.explosive-field-dispatch-form');
    }
}