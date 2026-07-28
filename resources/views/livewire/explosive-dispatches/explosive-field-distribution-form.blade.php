<div class="space-y-6">
    <flux:heading size="xl">Distribuir despacho — {{ $dispatch->dispatch_date->format('d/m/Y') }} ({{ $dispatch->shift === 'day' ? 'Día' : 'Noche' }})</flux:heading>

    @foreach ($rows as $index => $row)
        <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-900" wire:key="row-{{ $index }}">
            <div class="flex items-center justify-between">
                <flux:text class="font-medium">Fila {{ $index + 1 }}</flux:text>
                @if (count($rows) > 1)
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeRow({{ $index }})" />
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <flux:input label="Fecha" value="{{ $dispatch->dispatch_date->format('d/m/Y') }}" readonly />
                <flux:input label="Guardia" value="{{ $dispatch->shift === 'day' ? 'Día' : 'Noche' }}" readonly />

                <div>
                    <flux:label>Labor</flux:label>
                    <livewire:shared.entity-search-select
                        entityType="mining_labor"
                        fieldContext="row-{{ $index }}-labor"
                        :selectedId="$row['labor_id']"
                        :selectedLabel="$row['labor_label']"
                        wire:key="labor-{{ $index }}"
                    />
                </div>

                <flux:input label="Tipo de labor" value="{{ $row['labor_type'] ? ucfirst($row['labor_type']) : '—' }}" readonly />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <flux:label>Perforista</flux:label>
                    <livewire:shared.entity-search-select
                        entityType="employee"
                        fieldContext="row-{{ $index }}-driller"
                        :selectedId="$row['driller_id']"
                        :selectedLabel="$row['driller_label']"
                        wire:key="driller-{{ $index }}"
                    />
                </div>

                <flux:input type="number" wire:model="rows.{{ $index }}.guide_length_feet" label="Long guía (pies)" />
                <flux:input type="number" wire:model="rows.{{ $index }}.drill_depth_feet" label="Long barreno (pies)" />
            </div>

            <div class="grid gap-4" style="grid-template-columns: repeat(6, minmax(0, 1fr));">
                @foreach ($columns as $column => $label)
                    <flux:input type="number" step="0.01" wire:model.live="rows.{{ $index }}.{{ $column }}"
                        label="{{ $label }}" placeholder="0" />
                @endforeach
            </div>
        </flux:card>
    @endforeach

    <flux:button variant="ghost" icon="plus" wire:click="addRow">
        Agregar perforista
    </flux:button>

    {{-- ===== Subtotales en vivo ===== --}}
    <flux:card class="space-y-2">
        <flux:heading size="sm">Resumen de distribución</flux:heading>
        <div class="grid gap-3 md:grid-cols-3">
            @foreach ($this->totals as $t)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                    <flux:text size="sm" class="text-muted">{{ $t['label'] }}</flux:text>
                    <div class="text-sm">
                        <div>Despachado: {{ $t['requested'] }}</div>
                        <div>Distribuido: {{ $t['distributed'] }}</div>
                        <div class="font-medium {{ $t['remaining'] < 0 ? 'text-red-500' : ($t['remaining'] > 0 ? 'text-amber-500' : 'text-green-600') }}">
                            Remanente: {{ $t['remaining'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </flux:card>

    <div class="flex justify-end">
        <flux:button variant="primary" wire:click="reviewBeforeSave" icon="eye">Revisar y confirmar</flux:button>
    </div>

    <flux:modal wire:model.self="showConfirmation" class="md:w-[560px]">
        <div class="space-y-4">
            <flux:heading size="lg">Confirmar distribución</flux:heading>

            @foreach ($this->totals as $t)
                <flux:text size="sm">
                    {{ $t['label'] }}: remanente {{ $t['remaining'] }}
                    {{ $t['remaining'] > 0 ? '(quedará en Recepción)' : ($t['remaining'] < 0 ? '(se tomará de Recepción)' : '(cuadra exacto)') }}
                </flux:text>
            @endforeach

            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>Al confirmar, se actualizará el stock de Recepción según el remanente calculado.</flux:callout.text>
            </flux:callout>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="$set('showConfirmation', false)">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="confirmAndSave" icon="check">Confirmar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>