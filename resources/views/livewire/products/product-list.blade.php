<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Almacén</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Productos</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Productos</flux:heading>
            <flux:text class="mt-2">Catálogo de insumos para el Kardex.</flux:text>
        </div>
        <x-flex>
            @can(App\Constants\Permisos::PRODUCTOS_GESTIONAR)
                <flux:button variant="primary" icon="plus" href="{{ route('products.create') }}">
                    Nuevo producto
                </flux:button>
            @endcan
            <flux:button icon="arrow-down-tray" wire:click="exportToExcel" class="ml-2">
                Exportar a Excel
            </flux:button>
        </x-flex>
    </div>

    <x-flex class="justify-between flex-wrap gap-4">
        <x-flex class="flex-wrap gap-4">
            <div>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Buscar por nombre, código, barcode o código SUNAT..." class="w-auto" />

            </div>
            <flux:select wire:model.live="categoryId" placeholder="Categoría" class="w-auto">
                <flux:select.option value="">Todas las categorías</flux:select.option>
                @foreach ($this->categoryOptions as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-flex>

        <flux:field variant="inline">
            <flux:label>Ver eliminados</flux:label>
            <flux:switch wire:model.live="showTrashed" />
        </flux:field>
    </x-flex>

    @if ($showTrashed)
        <flux:callout variant="warning" icon="information-circle">
            <flux:callout.text>Mostrando productos dados de baja. Solo puedes restaurarlos desde aquí.</flux:callout.text>
        </flux:callout>
    @endif

    @if ($search || $categoryId)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-muted">Filtros activos:</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">Limpiar</flux:button>
        </div>
    @endif

    <flux:table :paginate="$products">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection"
                wire:click="sort('code')">
                Código
            </flux:table.column>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Categoría</flux:table.column>
            <flux:table.column>Unidad base</flux:table.column>
            <flux:table.column>Stock</flux:table.column>
            <flux:table.column>
                {{ $showTrashed ? 'Fecha de eliminación' : 'Estado' }}
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell variant="strong">{{ $product->code }}</flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col gap-1">
                            <span class="font-medium">{{ $product->name }}</span>
                            <div class="flex items-center gap-2">
                                @if($product->brand)
                                    <span class="text-muted text-sm">{{ $product->brand }}</span>
                                @endif
                                @if ($product->explosiveRole)
                                    <flux:badge size="sm" color="amber">{{ $product->explosiveRole->name }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>{{ $product->category->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->unit->alias ?: $product->unit->name }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($product->stocks->isEmpty())
                            <flux:text size="sm" class="text-muted">0</flux:text>
                        @else
                            <div class="flex flex-col gap-0.5">
                                @foreach ($this->warehouses as $warehouse)
                                    @php
                                        $stock = $product->stocks->firstWhere('warehouse_id', $warehouse->id);
                                        $cantidad = $stock->quantity ?? 0;
                                    @endphp
                                    @if ($cantidad > 0)
                                        <flux:text size="sm">
                                            <span class="font-medium">{{ $warehouse->name }}:</span>
                                            <span class="text-muted">
                                                {{ \App\Services\StockDisplayService::breakdown($product, (float) $cantidad) }}
                                            </span>
                                        </flux:text>
                                    @endif
                                @endforeach

                                @if ($product->stocks->sum('quantity') == 0)
                                    <flux:text size="sm" class="text-muted">0</flux:text>
                                @endif
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            <flux:text size="sm" class="text-muted">
                                {{ $product->deleted_at?->format('d/m/Y H:i') }}
                            </flux:text>
                        @else
                            <flux:badge size="sm" :color="$product->is_active ? 'green' : 'zinc'">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            @can(App\Constants\Permisos::PRODUCTOS_GESTIONAR)
                                <flux:button size="sm" variant="ghost" icon="arrow-path" wire:confirm="¿Restaurar este producto?"
                                    wire:click="restoreProduct({{ $product->id }})">
                                    Restaurar
                                </flux:button>
                            @endcan
                        @else
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="viewDetails({{ $product->id }})">
                                        Ver detalle completo
                                    </flux:menu.item>
                                    @can(App\Constants\Permisos::PRODUCTOS_GESTIONAR)
                                        <flux:menu.item icon="pencil" href="{{ route('products.edit', $product) }}">
                                            Editar producto
                                        </flux:menu.item>
                                        <flux:menu.item icon="archive-box"
                                            href="{{ route('products.edit', ['product' => $product->id, 'tab' => 'presentations']) }}">
                                            Gestionar presentaciones
                                        </flux:menu.item>
                                        <flux:menu.item icon="arrows-right-left" wire:click="openTransferModal({{ $product->id }})">
                                            Transferir entre almacenes
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:confirm="¿Eliminar este producto? Podrás restaurarlo después."
                                            wire:click="deleteProduct({{ $product->id }})">
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
                        <flux:text class="text-muted">No existen productos registrados con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:products.product-details-viewer />
    <livewire:products.warehouse-transfer-form />
</div>