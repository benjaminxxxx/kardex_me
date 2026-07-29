<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('explosive-dispatches.index') }}">
            Despachos
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Nuevo despacho</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo despacho de explosivos</flux:heading>
            <flux:text class="text-muted mt-2">
                Registre la salida de explosivos hacia campo.
            </flux:text>
        </div>

        <flux:button variant="ghost" icon="arrow-left" href="{{ route('explosive-dispatches.index') }}">
            Volver
        </flux:button>
    </div>

    @if (!$warehouseId)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Almacén no configurado</flux:callout.heading>
            <flux:callout.text>
                No hay un almacén asignado para salida a mina.
                <a href="{{ route('settings.company') }}" class="underline" wire:navigate>Configúralo aquí</a> antes de
                continuar.
            </flux:callout.text>
        </flux:callout>
    @endif

    <flux:card class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input type="date" wire:model="dispatchDate" label="Fecha" />
            <flux:select wire:model="shift" label="Guardia">
                <flux:select.option value="day">Día</flux:select.option>
                <flux:select.option value="night">Noche</flux:select.option>
            </flux:select>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input label="Almacenero (despacha)"
                value="{{ $this->dispatchedByEmployee?->person?->display_name ?? 'No identificado' }}" readonly />

            <flux:select wire:model="requestedByEmployeeId" label="Supervisor (retira)">
                <flux:select.option value="">Seleccionar supervisor...</flux:select.option>
                @foreach ($this->eligibleSupervisors as $employee)
                    <flux:select.option value="{{ $employee->id }}">{{ $employee->person->display_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:card class="space-y-6">
        <flux:heading size="sm">Cantidades requeridas</flux:heading>
        <flux:text class="text-muted text-sm">Deja en blanco lo que no se necesite en este despacho.</flux:text>

        <div class="grid gap-4" style="grid-template-columns: repeat({{ count($this->roles) }}, minmax(0, 1fr));">
            @foreach ($this->roles as $role)
                @php
                    $opciones = $this->productOptionsByRole[$role->code];
                    $sinStock = $opciones->every(fn($p) => $p['stock'] <= 0);
                @endphp

                <div class="space-y-2">
                    <flux:input type="number" step="0.01" wire:model.live="quantities.{{ $role->code }}"
                        label="{{ $role->name }}" placeholder="0" />

                    @if ($opciones->isEmpty())
                        <flux:callout variant="danger" icon="x-circle" class="p-2">
                            <flux:callout.text class="text-xs">Sin producto activo para este rol</flux:callout.text>
                        </flux:callout>
                    @elseif ($opciones->count() === 1)
                        @php $unico = $opciones->first(); @endphp
                        <div
                            class="flex items-center justify-between text-sm rounded-lg border border-zinc-200 dark:border-zinc-700 px-2 py-1.5">
                            <span class="text-muted truncate">{{ $unico['name'] }}</span>
                            <flux:badge size="sm" :color="$unico['stock'] > 0 ? 'green' : 'red'">
                                {{ $unico['stock'] }}
                            </flux:badge>
                        </div>
                    @else
                        <div
                            class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($opciones as $prod)
                                <label
                                    class="flex items-center justify-between gap-2 px-2 py-1.5 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    <span class="flex items-center gap-2 truncate">
                                        <input type="radio" wire:model="selectedProducts.{{ $role->code }}"
                                            value="{{ $prod['id'] }}">
                                        <span class="truncate">{{ $prod['name'] }}</span>
                                    </span>
                                    <flux:badge size="sm" :color="$prod['stock'] > 0 ? 'green' : 'red'">
                                        {{ $prod['stock'] }}
                                    </flux:badge>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @if ($sinStock && $opciones->isNotEmpty())
                        <flux:text size="sm" class="text-red-500">
                            Sin stock disponible en almacén.
                        </flux:text>
                    @endif
                </div>
            @endforeach
        </div>
    </flux:card>

    <flux:textarea wire:model="notes" label="Observaciones" />

    <div class="flex justify-end">
        <flux:button variant="primary" wire:click="reviewBeforeSave" icon="eye">
            Revisar antes de registrar
        </flux:button>
    </div>

    <flux:modal wire:model.self="showConfirmation" class="md:w-[560px]">
        <div class="space-y-4">
            <flux:heading size="lg">Confirmar despacho</flux:heading>

            <div class="grid gap-2 text-sm">
                <div><span class="text-muted">Fecha:</span> {{ $dispatchDate }} ·
                    {{ $shift === 'day' ? 'Día' : 'Noche' }}</div>
                <div><span class="text-muted">Almacenero:</span>
                    {{ $this->dispatchedByEmployee?->person?->display_name }}</div>
                <div><span class="text-muted">Supervisor:</span>
                    {{ $this->eligibleSupervisors->find($requestedByEmployeeId)?->person?->display_name }}</div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Rol</flux:table.column>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Cantidad</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->roles as $role)
                        @if (filled($quantities[$role->code]))
                            @php
                                $productoElegido = collect($this->productOptionsByRole[$role->code])
                                    ->firstWhere('id', $this->selectedProducts[$role->code] ?? null);
                            @endphp
                            <flux:table.row>
                                <flux:table.cell>{{ $role->name }}</flux:table.cell>
                                <flux:table.cell>{{ $productoElegido['name'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $quantities[$role->code] }}</flux:table.cell>
                            </flux:table.row>
                        @endif
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>Verifica las cantidades y productos. Una vez registrado, este despacho descuenta el
                    stock y queda en el Kardex.</flux:callout.text>
            </flux:callout>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="$set('showConfirmation', false)">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="confirmAndSave" icon="check">
                    Confirmar y registrar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>