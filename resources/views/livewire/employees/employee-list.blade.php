<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Recursos Humanos</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Empleados</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Empleados</flux:heading>
            <flux:text class="mt-2">Administra los trabajadores registrados en el sistema.</flux:text>
        </div>
        <x-flex>
            @can(App\Constants\Permisos::EMPLEADOS_GESTIONAR)
                <flux:button variant="primary" icon="plus" href="{{ route('employees.create') }}">
                    Nuevo empleado
                </flux:button>
            @endcan
            <flux:button icon="arrow-down-tray" wire:click="exportToExcel" class="ml-2">
                Exportar a Excel
            </flux:button>
        </x-flex>
    </div>

    {{-- ===== Filtros mejorados ===== --}}
    <div class="grid gap-4 md:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar..."
            autocomplete="off" name="person_lookup_query" {{-- 👈 nunca "email" , "user" , "login" --}} />

        <flux:select wire:model.live="status" placeholder="Estado">
            <flux:select.option value="">Todos los estados</flux:select.option>
            <flux:select.option value="active">Activo</flux:select.option>
            <flux:select.option value="inactive">Inactivo</flux:select.option>
            <flux:select.option value="suspended">Suspendido</flux:select.option>
            <flux:select.option value="terminated">Cesado</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="access" placeholder="Acceso al sistema">
            <flux:select.option value="">Todos</flux:select.option>
            <flux:select.option value="with_user">Con cuenta</flux:select.option>
            <flux:select.option value="without_user">Sin cuenta</flux:select.option>
        </flux:select>
    </div>

    @if ($search || $status || $access)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-zinc-500">Filtros activos:</flux:text>
            @if($search)
                <flux:badge size="sm">"{{ $search }}"</flux:badge>
            @endif
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">
                Limpiar
            </flux:button>
        </div>
    @endif

    <flux:table :paginate="$this->employees">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'employee_code'" :direction="$sortDirection"
                wire:click="sort('employee_code')">
                Código
            </flux:table.column>
            <flux:table.column>Empleado</flux:table.column>
            <flux:table.column>Documento</flux:table.column>
            <flux:table.column>Contacto</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'hire_date'" :direction="$sortDirection"
                wire:click="sort('hire_date')">
                Ingreso
            </flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column>Acceso</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->employees as $employee)
                <flux:table.row :key="$employee->id">

                    <flux:table.cell variant="strong">
                        {{ $employee->employee_code }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $employee->person->display_name }}</span>
                            @if($employee->person->email)
                                <span class="text-zinc-500 text-sm">{{ $employee->person->email }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span>{{ $employee->person->document_number }}</span>
                            <span class="text-zinc-500 text-xs">{{ $employee->person->document_type }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $employee->person->mobile ?: '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $employee->hire_date?->format('d/m/Y') ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $colors = ['active' => 'green', 'inactive' => 'zinc', 'suspended' => 'yellow', 'terminated' => 'red'];
                            $labels = ['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido', 'terminated' => 'Cesado'];
                        @endphp
                        <flux:badge size="sm" :color="$colors[$employee->status]">
                            {{ $labels[$employee->status] }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($employee->person->user)
                            <flux:badge size="sm" color="green" icon="check">Con acceso</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Sin acceso</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                <flux:menu.item icon="eye" wire:click="viewDetails({{ $employee->id }})">
                                    Ver detalle completo
                                </flux:menu.item>
                                @can(App\Constants\Permisos::EMPLEADOS_GESTIONAR)
                                    <flux:menu.item icon="user" wire:click="editPersonalInfo({{ $employee->person_id }})">
                                        Editar información personal
                                    </flux:menu.item>
                                    <flux:menu.item icon="briefcase" wire:click="editEmploymentInfo({{ $employee->id }})">
                                        Editar datos laborales
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="key" wire:click="manageAccess({{ $employee->person_id }})">
                                        Administrar acceso
                                    </flux:menu.item>
                                @endcan

                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-zinc-500">No existen empleados registrados con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:users.access-manager />
    <livewire:person.person-registrar />
    <livewire:employees.employee-details-editor />
    <livewire:employees.employee-details-viewer />
</div>