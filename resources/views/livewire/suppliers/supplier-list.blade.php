<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Compras</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Proveedores</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Proveedores</flux:heading>
            <flux:text class="mt-2">Administra los proveedores registrados en el sistema.</flux:text>
        </div>
        <x-flex>
            @can(App\Constants\Permisos::PROVEEDORES_GESTIONAR)
                <flux:button variant="primary" icon="plus" href="{{ route('suppliers.create') }}">
                    Nuevo proveedor
                </flux:button>
            @endcan
            <flux:button icon="arrow-down-tray" wire:click="exportToExcel" class="ml-2">
                Exportar a Excel
            </flux:button>
        </x-flex>
    </div>

    {{-- ===== Filtros ===== --}}
    <x-flex class="justify-between">
        <x-flex>
            <div>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar..."
                    autocomplete="off" name="supplier_lookup_query" class="w-auto" />
            </div>

            <flux:select wire:model.live="status" placeholder="Estado de homologación" class="w-auto">
                <flux:select.option value="">Todos los estados</flux:select.option>
                <flux:select.option value="prospect">Prospecto</flux:select.option>
                <flux:select.option value="approved">Homologado</flux:select.option>
                <flux:select.option value="suspended">Suspendido</flux:select.option>
                <flux:select.option value="blacklisted">Vetado</flux:select.option>
            </flux:select>
        </x-flex>

        <flux:field variant="inline">
            <flux:label>Ver eliminados</flux:label>
            <flux:switch wire:model.live="showTrashed" />
        </flux:field>
    </x-flex>

    @if ($showTrashed)
        <flux:callout variant="warning" icon="information-circle">
            <flux:callout.text>Mostrando proveedores dados de baja. Solo puedes restaurarlos desde aquí.</flux:callout.text>
        </flux:callout>
    @endif

    @if ($search || $status)
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

    <flux:table :paginate="$suppliers">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'supplier_code'" :direction="$sortDirection"
                wire:click="sort('supplier_code')">
                Código
            </flux:table.column>
            <flux:table.column>Proveedor</flux:table.column>
            <flux:table.column>Documento</flux:table.column>
            <flux:table.column>Contacto</flux:table.column>
            <flux:table.column>
                {{ $showTrashed ? 'Fecha de baja' : 'Estado' }}
            </flux:table.column>
            <flux:table.column>Acceso</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($suppliers as $supplier)
                <flux:table.row :key="$supplier->id">

                    <flux:table.cell variant="strong">
                        {{ $supplier->supplier_code }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $supplier->person->display_name }}</span>
                            @if($supplier->person->type === 'company')
                                <span class="text-zinc-500 text-sm">{{ $supplier->person->company_name }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span>{{ $supplier->person->document_number }}</span>
                            <span class="text-zinc-500 text-xs">{{ $supplier->person->document_type }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $supplier->person->mobile ?: ($supplier->person->phone ?: '—') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $colors = ['prospect' => 'zinc', 'approved' => 'green', 'suspended' => 'yellow', 'blacklisted' => 'red'];
                            $labels = ['prospect' => 'Prospecto', 'approved' => 'Homologado', 'suspended' => 'Suspendido', 'blacklisted' => 'Vetado'];
                        @endphp
                        @if ($showTrashed)
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $supplier->deleted_at?->format('d/m/Y H:i') }}
                            </flux:text>
                        @else
                            <flux:badge size="sm" :color="$colors[$supplier->status]">
                                {{ $labels[$supplier->status] }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($supplier->person->user)
                            <flux:badge size="sm" color="green" icon="check">Con acceso</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Sin acceso</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            @can(App\Constants\Permisos::PROVEEDORES_GESTIONAR)
                                <flux:button size="sm" variant="ghost" icon="arrow-path"
                                    wire:confirm="¿Restaurar este proveedor? Volverá a aparecer como activo en el sistema."
                                    wire:click="restoreSupplier({{ $supplier->id }})">
                                    Restaurar
                                </flux:button>
                            @endcan
                        @else
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="viewDetails({{ $supplier->id }})">
                                        Ver detalle completo
                                    </flux:menu.item>
                                    @can(App\Constants\Permisos::PROVEEDORES_GESTIONAR)
                                        <flux:menu.item icon="user" wire:click="editPersonalInfo({{ $supplier->person_id }})">
                                            Editar información personal / empresa
                                        </flux:menu.item>
                                        <flux:menu.item icon="briefcase" wire:click="editSupplierInfo({{ $supplier->id }})">
                                            Editar datos de homologación
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="map-pin" wire:click="manageBranches({{ $supplier->id }})">
                                            Direcciones / sucursales
                                        </flux:menu.item>
                                        <flux:menu.item icon="banknotes" wire:click="manageBankAccounts({{ $supplier->id }})">
                                            Métodos de pago
                                        </flux:menu.item>
                                        <flux:menu.item icon="key" wire:click="manageAccess({{ $supplier->person_id }})">
                                            Administrar acceso
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:confirm="¿Seguro que deseas dar de baja a este proveedor? Se revocará su acceso al sistema (si lo tiene) y se eliminará el registro de proveedor. La información de la persona se conservará."
                                            wire:click="deactivateSupplier({{ $supplier->id }})">
                                            Dar de baja a proveedor (eliminar)
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-zinc-500">No existen proveedores registrados con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:suppliers.supplier-details-viewer />
    <livewire:users.access-manager />
    <livewire:person.person-registrar />
    <livewire:suppliers.supplier-editor />

</div>