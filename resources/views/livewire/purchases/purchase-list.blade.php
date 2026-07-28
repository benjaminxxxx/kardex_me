<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Compras</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Compras</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Compras</flux:heading>
            <flux:text class="text-muted mt-2">Registro de compras a proveedores.</flux:text>
        </div>
        <x-flex>
            @can(App\Constants\Permisos::COMPRAS_GESTIONAR)
                <flux:button variant="primary" icon="plus" href="{{ route('purchases.create') }}">
                    Nueva compra
                </flux:button>
            @endcan
            <flux:button icon="arrow-down-tray" wire:click="exportToExcel" class="ml-2">
                Exportar a Excel
            </flux:button>
        </x-flex>
    </div>

    {{-- ===== Filtros ===== --}}
    <x-flex class="justify-between flex-wrap gap-4">
        <x-flex class="flex-wrap gap-4">
            <div>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Buscar por N° comprobante..." class="w-auto" />
            </div>

            <flux:select wire:model.live="documentType" placeholder="Tipo de comprobante" class="w-auto">
                <flux:select.option value="">Todos los tipos</flux:select.option>
                <flux:select.option value="factura">Factura</flux:select.option>
                <flux:select.option value="boleta">Boleta</flux:select.option>
                <flux:select.option value="nota_venta">Nota de venta</flux:select.option>
            </flux:select>

            <div>
                <flux:input type="date" wire:model.live="dateFrom" class="w-auto" />
            </div>
            <div>
                <flux:input type="date" wire:model.live="dateTo" class="w-auto" />
            </div>

            <div class="w-56">
                <livewire:shared.entity-search-select entityType="supplier" fieldContext="purchase-filter-supplier"
                    :selectedId="$supplierId" :selectedLabel="$supplierLabel" />
            </div>
        </x-flex>

        <flux:field variant="inline">
            <flux:label>Ver eliminadas</flux:label>
            <flux:switch wire:model.live="showTrashed" />
        </flux:field>
    </x-flex>

    @if ($showTrashed)
        <flux:callout variant="warning" icon="information-circle">
            <flux:callout.text>Mostrando compras dadas de baja. Solo puedes restaurarlas desde aquí.</flux:callout.text>
        </flux:callout>
    @endif

    @if ($search || $documentType || $dateFrom || $dateTo || $supplierId)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-muted">Filtros activos:</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">Limpiar</flux:button>
        </div>
    @endif

    <flux:table :paginate="$purchases">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'document_date'" :direction="$sortDirection"
                wire:click="sort('document_date')">
                Fecha
            </flux:table.column>
            <flux:table.column>Proveedor</flux:table.column>
            <flux:table.column>Comprobante</flux:table.column>
            <flux:table.column>Almacén</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'total'" :direction="$sortDirection"
                wire:click="sort('total')">
                Total
            </flux:table.column>
            <flux:table.column>
                {{ $showTrashed ? 'Fecha de eliminación' : 'Forma de pago' }}
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($purchases as $purchase)
                <flux:table.row :key="$purchase->id">
                    <flux:table.cell variant="strong">{{ $purchase->document_date->format('d/m/Y') }}</flux:table.cell>

                    <flux:table.cell>{{ $purchase->supplier->person->display_name }}</flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $purchase->document_type)) }}</span>
                            @if($purchase->document_number)
                                <span class="text-muted text-sm">{{ $purchase->document_number }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>{{ $purchase->warehouse->name }}</flux:table.cell>

                    <flux:table.cell>{{ $purchase->currency }} {{ number_format($purchase->total, 2) }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            <flux:text size="sm" class="text-muted">
                                {{ $purchase->deleted_at?->format('d/m/Y H:i') }}
                            </flux:text>
                        @else
                            <flux:badge size="sm" :color="$purchase->payment_method === 'contado' ? 'green' : 'amber'">
                                {{ ucfirst($purchase->payment_method) }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            @can(App\Constants\Permisos::COMPRAS_GESTIONAR)
                                <flux:button size="sm" variant="ghost" icon="arrow-path" wire:confirm="¿Restaurar esta compra?"
                                    wire:click="restorePurchase({{ $purchase->id }})">
                                    Restaurar
                                </flux:button>
                            @endcan
                        @else
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    @can(App\Constants\Permisos::COMPRAS_GESTIONAR)
                                        <flux:menu.item icon="pencil" href="{{ route('purchases.edit', $purchase) }}">
                                            Editar compra
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:confirm="¿Eliminar esta compra? Podrás restaurarla después."
                                            wire:click="deletePurchase({{ $purchase->id }})">
                                            Eliminar
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
                        <flux:text class="text-muted">No existen compras registradas con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>