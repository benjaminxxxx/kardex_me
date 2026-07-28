<?php

namespace App\Livewire\Roles;

use App\Services\PermisosServicio;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Permisos del rol')]
class RolePermissionsManager extends Component
{
    public int $roleId;
    public string $roleName = '';
    public array $arbol = [];
    public array $permisosActivados = [];

    public function mount(Role $role): void
    {
        $this->roleId = $role->id;
        $this->roleName = $role->name;
        $this->arbol = config('permisos_tree');
        $this->permisosActivados = PermisosServicio::obtenerPermisosDeRol($role->id);
    }

    public function guardar(): void
    {
        try {
            PermisosServicio::guardarPermisosParaRol($this->roleId, $this->permisosActivados);
            Flux::toast('Permisos actualizados correctamente.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.roles.role-permissions-manager');
    }
}