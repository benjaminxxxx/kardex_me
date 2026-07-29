<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('kardex.index') }}">Kardex</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $kardex->product->name }} - {{ $kardex->month }}/{{ $kardex->year }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $this->pageTitle }}</flux:heading>
            <flux:text class="text-muted mt-2">
                Método de costeo:
                <strong>{{ $kardex->costing_method === 'average' ? 'Promedio Ponderado' : 'FIFO' }}</strong>
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:badge size="lg" :color="$kardex->status === 'closed' ? 'green' : 'amber'">
                {{ $kardex->status === 'closed' ? 'Cerrado' : 'Abierto' }}
            </flux:badge>
            @if ($kardex->excel_path)
                <flux:button icon="arrow-down-tray" href="{{ Storage::disk('public')->url($kardex->excel_path) }}" download>
                    Último Kardex Generado</flux:button>
            @endif
        </div>
    </div>

    {{-- ===== Resumen ===== --}}
    <div class="grid gap-4 md:grid-cols-4">

        <flux:card class="space-y-1">
            <div class="flex items-center justify-between">
                <flux:text size="sm" class="text-muted">Saldo inicial</flux:text>
                @if ($kardex->status === 'open')
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openOpeningEditor" />
                @endif
            </div>
            <flux:heading size="lg">{{ number_format($kardex->opening_qty, 4) }}</flux:heading>
            <flux:text size="sm" class="text-muted">
                Costo unit: {{ number_format($kardex->opening_unit_cost, 6) }} · Total:
                {{ number_format($kardex->opening_total_cost, 2) }}
            </flux:text>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-muted">Entradas del periodo</flux:text>
            <flux:heading size="lg" class="text-green-600">+{{ number_format($kardex->total_entries_qty, 4) }}
            </flux:heading>
            <flux:text size="sm" class="text-muted">Costo total: {{ number_format($kardex->total_entries_cost, 2) }}
            </flux:text>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-muted">Salidas del periodo</flux:text>
            <flux:heading size="lg" class="text-red-500">-{{ number_format($kardex->total_exits_qty, 4) }}
            </flux:heading>
            <flux:text size="sm" class="text-muted">Costo total: {{ number_format($kardex->total_exits_cost, 2) }}
            </flux:text>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text size="sm" class="text-muted">Saldo final</flux:text>
            <flux:heading size="lg">{{ number_format($kardex->closing_qty, 4) }}</flux:heading>
            <flux:text size="sm" class="text-muted">
                Costo unit: {{ number_format($kardex->closing_unit_cost, 6) }} · Total:
                {{ number_format($kardex->closing_total_cost, 2) }}
            </flux:text>
        </flux:card>
    </div>

    @if ($kardex->status === 'open')
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.text>
                Este kardex está abierto. Puedes recalcularlo si hubo cambios en los movimientos del periodo.
                Al cerrarlo, el saldo final quedará fijo y se usará como saldo inicial del siguiente mes.
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" variant="ghost" wire:click="recalculate" icon="arrow-path">Recalcular</flux:button>
                <flux:button size="sm" variant="primary"
                    wire:confirm="¿Cerrar este kardex? El saldo final quedará fijo y no podrás recalcularlo después."
                    wire:click="close">
                    Cerrar kardex
                </flux:button>
            </x-slot>
        </flux:callout>
    @endif

    {{-- ===== Detalle de movimientos, estilo hoja de cálculo ===== --}}
    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Origen</flux:table.column>
                <flux:table.column>Entrada Cant.</flux:table.column>
                <flux:table.column>Entrada C.U.</flux:table.column>
                <flux:table.column>Entrada Total</flux:table.column>
                <flux:table.column>Salida Cant.</flux:table.column>
                <flux:table.column>Salida C.U.</flux:table.column>
                <flux:table.column>Salida Total</flux:table.column>
                <flux:table.column>Saldo Cant.</flux:table.column>
                <flux:table.column>Saldo C.U.</flux:table.column>
                <flux:table.column>Saldo Total</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                {{-- Fila de saldo inicial, siempre primero --}}
                <flux:table.row class="bg-zinc-50 dark:bg-zinc-900 font-medium">
                    <flux:table.cell colspan="8">Saldo inicial del periodo</flux:table.cell>
                    <flux:table.cell>{{ number_format($kardex->opening_qty, 0) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($kardex->opening_unit_cost, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($kardex->opening_total_cost, 2) }}</flux:table.cell>
                </flux:table.row>

                @forelse ($kardex->movements as $mov)
                    <flux:table.row :key="$mov->id">
                        <flux:table.cell>{{ $mov->movement_date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $mov->source_label }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-green-600">
                            {{ $mov->entry_qty ? number_format($mov->entry_qty, 0) : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $mov->entry_unit_cost ? number_format($mov->entry_unit_cost, 2) : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $mov->entry_total_cost ? number_format($mov->entry_total_cost, 2) : '-' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-red-500">{{ $mov->exit_qty ? number_format($mov->exit_qty, 0) : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $mov->exit_unit_cost ? number_format($mov->exit_unit_cost, 2) : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $mov->exit_total_cost ? number_format($mov->exit_total_cost, 2) : '-' }}
                        </flux:table.cell>

                        <flux:table.cell class="font-medium">{{ number_format($mov->balance_qty, 0) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($mov->balance_unit_cost, 2) }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ number_format($mov->balance_total_cost, 2) }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="11" class="text-center">
                            <flux:text class="text-muted">Sin movimientos en este periodo.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse

                {{-- Fila de saldo final, siempre al cierre --}}
                <flux:table.row class="bg-zinc-50 dark:bg-zinc-900 font-bold">
                    <flux:table.cell colspan="8">Saldo final del periodo</flux:table.cell>
                    <flux:table.cell>{{ number_format($kardex->closing_qty, 0) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($kardex->closing_unit_cost, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($kardex->closing_total_cost, 2) }}</flux:table.cell>
                </flux:table.row>
            </flux:table.rows>
        </flux:table>
    </div>
    <flux:modal wire:model.self="showOpeningEditor" class="md:w-[440px]">
        <div class="space-y-4">
            <flux:heading size="lg">Editar saldo inicial</flux:heading>
            <flux:text class="text-muted text-sm">
                Al guardar, el kardex completo se recalculará usando este nuevo punto de partida.
            </flux:text>

            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="pullFromPreviousMonth" class="w-full">
                Extraer del mes anterior
            </flux:button>

            <flux:input type="number" step="0.0001" wire:model="editOpeningQty" label="Cantidad inicial" />
            <flux:input type="number" step="0.000001" wire:model="editOpeningUnitCost" label="Costo unitario inicial" />

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="$set('showOpeningEditor', false)">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="saveOpeningBalance" icon="check">Guardar y recalcular
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>