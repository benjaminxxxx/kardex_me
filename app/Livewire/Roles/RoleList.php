<?php

namespace App\Livewire\Roles;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Roles')]
class RoleList extends Component
{
    public bool $showCreateModal = false;
    public string $newRoleName = '';

    // Roles que no se pueden eliminar/renombrar desde la UI
    protected array $rolesProtegidos = ['Developer', 'Admin'];

    public function getRolesProperty()
    {
        return Role::withCount('users')->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->reset('newRoleName');
        $this->showCreateModal = true;
    }

    public function createRole(): void
    {
        $this->validate([
            'newRoleName' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')],
        ]);

        Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);

        Flux::toast('Rol creado correctamente. Ahora asígnale permisos.');
        $this->showCreateModal = false;
        $this->reset('newRoleName');
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        if (in_array($role->name, $this->rolesProtegidos)) {
            Flux::toast('Este rol es del sistema y no se puede eliminar.', 'Error');
            return;
        }

        if ($role->users()->count() > 0) {
            Flux::toast('No puedes eliminar un rol que tiene usuarios asignados.', 'Error');
            return;
        }

        $role->delete();
        Flux::toast('Rol eliminado correctamente.');
    }

    public function sincronizarPermisos(\App\Services\PermisosServicio $servicio): void
    {
        $resultado = \App\Services\PermisosServicio::sincronizarPermisosEnBD();

        Flux::toast("Sincronización completa: {$resultado['creados']} permisos nuevos, {$resultado['eliminados']} eliminados.");
    }

    public function render()
    {
        return view('livewire.roles.role-list', [
            'roles' => $this->roles,
        ]);
    }
}