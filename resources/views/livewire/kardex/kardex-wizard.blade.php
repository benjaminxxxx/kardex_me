<div class="space-y-6">
    <flux:heading size="xl">Nuevo Kardex</flux:heading>

    @if ($step === 1)
        <flux:card class="space-y-4">
            <flux:label>Buscar producto</flux:label>
            <livewire:shared.entity-search-select entityType="product" fieldContext="kardex-product" />
        </flux:card>
    @endif

    @if ($step === 2)
        <flux:card class="space-y-6">
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ $productLabel }}</flux:callout.heading>
            </flux:callout>

            @if ($this->openKardex)
                {{-- Ya hay un kardex abierto: no se puede crear otro, se dirige a ese --}}
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>
                        Este producto ya tiene un kardex abierto ({{ $this->openKardex->month }}/{{ $this->openKardex->year }}).
                        Debes cerrarlo antes de crear uno nuevo.
                    </flux:callout.text>
                </flux:callout>
                <flux:button variant="primary" href="{{ route('kardex.show', $this->openKardex) }}">
                    Ir al kardex abierto
                </flux:button>

            @elseif ($this->isFirstKardex)
                {{-- ===== Primer kardex del producto: mes/año libre ===== --}}
                <flux:text class="text-muted text-sm">
                    Este producto no tiene kardex previo. Elige libremente el mes de inicio -
                    el sistema sugerirá el saldo inicial calculado desde los movimientos reales
                    anteriores a esa fecha, pero puedes ajustarlo manualmente.
                </flux:text>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select wire:model.live="year" label="Año">
                        @for ($y = now()->year; $y >= now()->year - 3; $y--)
                            <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                        @endfor
                    </flux:select>

                    <flux:select wire:model.live="month" label="Mes de inicio">
                        @foreach (['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                            <flux:select.option value="{{ $i + 1 }}">{{ $nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button variant="ghost" wire:click="suggestOpeningBalance" icon="calculator">
                    Sugerir saldo inicial desde movimientos
                </flux:button>

                @if ($suggested)
                    <flux:callout variant="success" icon="check-circle">
                        <flux:callout.text>
                            Saldo sugerido calculado con los movimientos anteriores a {{ $month }}/{{ $year }}.
                            Puedes ajustarlo si es necesario.
                        </flux:callout.text>
                    </flux:callout>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input type="number" step="0.0001" wire:model="openingQty" label="Cantidad inicial" />
                    <flux:input type="number" step="0.000001" wire:model="openingUnitCost" label="Costo unitario inicial" />
                </div>

                <flux:button variant="primary" wire:click="createFirstKardex" icon="check">
                    Crear kardex inicial
                </flux:button>

            @else
                {{-- ===== Ya existe historial: flujo secuencial obligatorio ===== --}}
                <flux:select wire:model.live="year" label="Año">
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                    @endfor
                </flux:select>

                <flux:heading size="sm">Meses cerrados en {{ $year }}</flux:heading>
                <div class="grid gap-2 md:grid-cols-4">
                    @forelse ($this->closedMonths as $k)
                        <flux:badge color="green">Mes {{ $k->month }} ✓</flux:badge>
                    @empty
                        <flux:text class="text-muted">Ningún mes cerrado en este año todavía.</flux:text>
                    @endforelse
                </div>

                <flux:callout variant="warning" icon="arrow-right">
                    <flux:callout.text>Siguiente periodo a crear: <strong>{{ $this->nextPeriodLabel }}</strong></flux:callout.text>
                </flux:callout>

                <flux:button variant="primary" wire:click="createNextKardex" icon="check">
                    Crear y calcular {{ $this->nextPeriodLabel }}
                </flux:button>
            @endif
        </flux:card>
    @endif
</div>