<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Sistema</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Roles</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Roles y permisos</flux:heading>
            <flux:text class="mt-2">Administra los roles del sistema y sus permisos asignados.</flux:text>
        </div>
        <x-flex>
            @can(App\Constants\Permisos::ROLES_GESTIONAR)
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                    Nuevo rol
                </flux:button>
            @endcan
            @can(App\Constants\Permisos::PERMISOS_SINCRONIZAR)
                <flux:button icon="arrow-path"
                    wire:confirm="¿Sincronizar el árbol de permisos? Esto crea los permisos nuevos definidos en config/permisos_tree.php y elimina los que ya no existan ahí."
                    wire:click="sincronizarPermisos">
                    Sincronizar permisos
                </flux:button>
            @endcan
        </x-flex>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Rol</flux:table.column>
            <flux:table.column>Usuarios asignados</flux:table.column>
            <flux:table.column>Permisos activos</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($roles as $role)
                <flux:table.row :key="$role->id">
                    <flux:table.cell variant="strong">{{ $role->name }}</flux:table.cell>
                    <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                    <flux:table.cell>{{ $role->permissions()->count() }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                @can(App\Constants\Permisos::ROLES_GESTIONAR)
                                    <flux:menu.item icon="key" href="{{ route('roles.permissions', $role) }}">
                                        Gestionar permisos
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger"
                                        wire:confirm="¿Eliminar este rol? Esta acción no se puede deshacer."
                                        wire:click="deleteRole({{ $role->id }})">
                                        Eliminar rol
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-zinc-500">No existen roles registrados.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showCreateModal" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">Nuevo rol</flux:heading>
            <flux:input wire:model="newRoleName" label="Nombre del rol" placeholder="Ej: Contable, Almacenero..." />
            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="createRole" icon="check">Crear rol</flux:button>
            </div>
        </div>
    </flux:modal>
</div>