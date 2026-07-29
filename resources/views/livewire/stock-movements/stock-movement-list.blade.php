<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Kardex</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Movimientos</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div>
        <flux:heading size="xl">Movimientos de stock</flux:heading>
        <flux:text class="text-muted mt-2">Vista atómica de entradas y salidas, independiente del proceso que las originó.</flux:text>
    </div>

    {{-- ===== Filtros ===== --}}
    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="direction" placeholder="Tipo">
            <flux:select.option value="">Todos (entradas y salidas)</flux:select.option>
            <flux:select.option value="in">Solo entradas</flux:select.option>
            <flux:select.option value="out">Solo salidas</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="warehouseId" placeholder="Almacén">
            <flux:select.option value="">Todos los almacenes</flux:select.option>
            @foreach ($this->warehouses as $wh)
                <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="year" placeholder="Año">
            <flux:select.option value="">Todos los años</flux:select.option>
            @foreach ($this->availableYears as $y)
                <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="month" placeholder="Mes">
            <flux:select.option value="">Todos los meses</flux:select.option>
            @foreach (range(1, 12) as $m)
                <flux:select.option value="{{ $m }}">{{ ucfirst(\Carbon\Carbon::create()->month($m)->translatedFormat('F')) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:input type="date" wire:model.live="dateFrom" label="Desde" />
        <flux:input type="date" wire:model.live="dateTo" label="Hasta" />

        <div>
            <flux:label>Producto</flux:label>
            <livewire:shared.entity-search-select
                entityType="product"
                fieldContext="movement-filter-product"
                :selectedId="$productId"
                :selectedLabel="$productLabel"
            />
        </div>
    </div>

    @if ($direction || $warehouseId || $year || $month || $dateFrom || $dateTo || $productId)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-muted">Filtros activos:</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">Limpiar</flux:button>
        </div>
    @endif

    <flux:table :paginate="$movements">
        <flux:table.columns>
            <flux:table.column>Fecha</flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column>Producto</flux:table.column>
            <flux:table.column>Cantidad</flux:table.column>
            <flux:table.column>Almacén</flux:table.column>
            <flux:table.column>Origen</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($movements as $movement)
                <flux:table.row :key="$movement->id">
                    <flux:table.cell>{{ $movement->movement_date->format('d/m/Y') }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$movement->direction === 'in' ? 'green' : 'red'">
                            {{ $movement->direction === 'in' ? 'Entrada' : 'Salida' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $movement->product->name }}</span>
                            @if($movement->product->brand)
                                <span class="text-muted text-sm">{{ $movement->product->brand }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="{{ $movement->direction === 'in' ? 'text-green-600' : 'text-red-500' }} font-medium">
                        {{ $movement->direction === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                    </flux:table.cell>

                    <flux:table.cell>{{ $movement->warehouse->name }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ $movement->source_label }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-muted">No hay movimientos con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>