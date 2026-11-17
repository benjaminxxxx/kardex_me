<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Kardex</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Kardex</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Kardex</flux:heading>
            <flux:text class="text-muted mt-2">Registro valorizado mensual por producto.</flux:text>
        </div>
        @can(App\Constants\Permisos::KARDEX_GESTIONAR)
            <flux:button variant="primary" icon="plus" href="{{ route('kardex.wizard') }}">
                Nuevo kardex
            </flux:button>
        @endcan
    </div>

    {{-- ===== Filtros ===== --}}
    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="year" placeholder="Año">
            <flux:select.option value="">Todos los años</flux:select.option>
            @foreach ($this->availableYears as $y)
                <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="month" placeholder="Mes">
            <flux:select.option value="">Todos los meses</flux:select.option>
            @foreach (range(1, 12) as $m)
                <flux:select.option value="{{ $m }}">
                    {{ ucfirst(\Carbon\Carbon::create()->month($m)->translatedFormat('F')) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" placeholder="Estado">
            <flux:select.option value="">Todos los estados</flux:select.option>
            <flux:select.option value="open">Abierto</flux:select.option>
            <flux:select.option value="closed">Cerrado</flux:select.option>
        </flux:select>

        <div>
            <livewire:shared.entity-search-select entityType="product" fieldContext="kardex-filter-product"
                :selectedId="$productId" :selectedLabel="$productLabel" />
        </div>
    </div>

    @if ($overdue->isNotEmpty())
        <flux:callout variant="warning" icon="calendar-days">
            <flux:callout.heading>Recordatorio de cierre mensual</flux:callout.heading>
            <flux:callout.text>
                Hay {{ $overdue->count() }} kárdex de meses que ya terminaron y siguen abiertos. Ciérralos para fijar el
                saldo inicial del mes siguiente:
                {{ $overdue->map(fn($k) => ($k->product?->name ?? '#' . $k->product_id) . ' ' . str_pad($k->month, 2, '0', STR_PAD_LEFT) . '/' . $k->year)->join(', ') }}.
            </flux:callout.text>
        </flux:callout>
    @endif

    @if (count($mismatches))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Saldos iniciales que no coinciden</flux:callout.heading>
            <flux:callout.text>
                {{ count($mismatches) }} kárdex tienen un saldo inicial distinto al saldo final del mes anterior.
                Están marcados en la tabla. Abre cada uno, edita el saldo inicial y usa "Extraer del mes anterior", o
                verifica si el ajuste fue intencional.
            </flux:callout.text>
        </flux:callout>
    @endif

    @if ($year || $month || $status || $productId)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-muted">Filtros activos:</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">Limpiar</flux:button>
        </div>
    @endif

    <flux:table :paginate="$kardexes">
        <flux:table.columns>
            <flux:table.column>Producto</flux:table.column>
            <flux:table.column>Periodo</flux:table.column>
            <flux:table.column>Método</flux:table.column>
            <flux:table.column>Saldo inicial</flux:table.column>
            <flux:table.column>Entradas</flux:table.column>
            <flux:table.column>Salidas</flux:table.column>
            <flux:table.column>Saldo final</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($kardexes as $kardex)
                @php
                    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                @endphp
                <flux:table.row :key="$kardex->id">
                    <flux:table.cell variant="strong">
                        <div class="flex flex-col">
                            <span>{{ $kardex->product->name }}</span>
                            @if($kardex->product->brand)
                                <span class="text-muted text-sm">{{ $kardex->product->brand }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>{{ $meses[$kardex->month] }} {{ $kardex->year }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">
                            {{ $kardex->costing_method === 'average' ? 'Promedio' : 'FIFO' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($m = $mismatches[$kardex->id] ?? null)
                            <flux:tooltip content="El mes anterior ({{ $m['previous_period'] }}) cerró con {{ number_format($m['previous_closing'], 2) }}. Diferencia: {{ number_format($m['difference'], 2) }}">
                                <span class="inline-flex items-center gap-1 font-medium text-red-600 dark:text-red-400">
                                    <flux:icon.exclamation-triangle variant="micro" />
                                    {{ number_format($kardex->opening_qty, 2) }}
                                </span>
                            </flux:tooltip>
                        @else
                            {{ number_format($kardex->opening_qty, 2) }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-green-600">+{{ number_format($kardex->total_entries_qty, 2) }}
                    </flux:table.cell>
                    <flux:table.cell class="text-red-500">-{{ number_format($kardex->total_exits_qty, 2) }}
                    </flux:table.cell>
                    <flux:table.cell class="font-medium">{{ number_format($kardex->closing_qty, 2) }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$kardex->status === 'closed' ? 'green' : 'amber'">
                            {{ $kardex->status === 'closed' ? 'Cerrado' : 'Abierto' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('kardex.show', $kardex) }}">
                            Ver
                        </flux:button>
                        @if ($kardex->status !== 'closed')
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmDelete({{ $kardex->id }})">
                                Eliminar
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-muted">No existen kardex registrados con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <flux:modal wire:model="deleteKardexModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar Kárdex?</flux:heading>

                <flux:text class="mt-2">
                    Está a punto de eliminar este registro de Kárdex.<br>
                    Esta acción no se puede deshacer. Quedará registrada en Auditoría con una copia de sus saldos.
                </flux:text>
            </div>

            <flux:textarea wire:model="deletionReason" label="Motivo de la eliminación" rows="3"
                placeholder="Ej.: se debe recalcular tras corregir una compra..." />

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteKardex" wire:loading.attr="disabled">
                    Eliminar Kárdex
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>