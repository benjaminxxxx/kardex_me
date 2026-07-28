<div class="grid gap-6 md:grid-cols-[380px_1fr]">

    {{-- ===== Columna izquierda: cabecera ===== --}}
    <flux:card class="space-y-4">
        <div class="rounded-lg bg-amber-500 text-white text-center py-3 font-bold text-lg">
            TOTAL S/. {{ number_format($this->total, 2) }}
        </div>

        <div>
            <flux:label>Proveedor</flux:label>
            <livewire:shared.entity-search-select
                entityType="supplier"
                fieldContext="purchase-supplier"
                :selectedId="$supplierId"
                :selectedLabel="$supplierLabel"
            />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="currency" label="Moneda">
                <flux:select.option value="PEN">Soles (PEN)</flux:select.option>
                <flux:select.option value="USD">Dólares (USD)</flux:select.option>
            </flux:select>
            <flux:input type="number" step="0.0001" wire:model="exchangeRate" label="T.C. (si es USD)"
                :disabled="$currency === 'PEN'" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="documentType" label="Tipo de comprobante">
                <flux:select.option value="factura">Factura</flux:select.option>
                <flux:select.option value="boleta">Boleta</flux:select.option>
                <flux:select.option value="nota_venta">Nota de venta</flux:select.option>
            </flux:select>
            <flux:input wire:model="documentNumber" label="N° comprobante" placeholder="Opcional" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input type="date" wire:model="documentDate" label="Fecha comprobante" />
            <flux:input type="date" wire:model="dueDate" label="Fecha vencimiento" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="paymentMethod" label="Forma de pago">
                <flux:select.option value="contado">Contado</flux:select.option>
                <flux:select.option value="credito">Crédito</flux:select.option>
            </flux:select>

            <flux:select wire:model="warehouseId" label="Almacén">
                <flux:select.option value="">Seleccionar...</flux:select.option>
                @foreach ($this->warehouses as $wh)
                    <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="notes" label="Glosa / Observación" placeholder="Detalles adicionales de la compra" />

        <flux:button variant="primary" icon="check" wire:click="save" class="w-full">
            Registrar Compra
        </flux:button>
    </flux:card>

    {{-- ===== Columna derecha: buscador + detalle ===== --}}
    <div class="space-y-4">
        <flux:card class="space-y-4">
            <flux:label>Buscar producto</flux:label>
            <livewire:shared.entity-search-select
                entityType="product"
                fieldContext="purchase-product"
                :key="'product-search-'.count($items)"
            />

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column>Presentación</flux:table.column>
                        <flux:table.column>Cant.</flux:table.column>
                        <flux:table.column>Costo Unit.</flux:table.column>
                        <flux:table.column>Desc. (%)</flux:table.column>
                        <flux:table.column>IGV (%)</flux:table.column>
                        <flux:table.column>Total línea</flux:table.column>
                        <flux:table.column>Quitar</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($items as $index => $item)
                            <flux:table.row wire:key="item-{{ $index }}">
                                <flux:table.cell>{{ $item['product_label'] }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:select wire:model.live="items.{{ $index }}.presentation_id" size="sm">
                                        <flux:select.option value="">{{ '(unidad base)' }}</flux:select.option>
                                        @foreach ($this->getPresentationOptions($index) as $pres)
                                            <flux:select.option value="{{ $pres->id }}">
                                                {{ $pres->name }} x{{ (int) $pres->conversion_factor }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:input type="number" step="0.01" wire:model.live="items.{{ $index }}.quantity" size="sm" class="w-20" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_cost" size="sm" class="w-24" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:input type="number" step="0.01" wire:model.live="items.{{ $index }}.discount_percent" size="sm" class="w-16" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:input type="number" step="0.01" wire:model.live="items.{{ $index }}.igv_percent" size="sm" class="w-16" />
                                </flux:table.cell>

                                <flux:table.cell class="font-medium">
                                    S/. {{ number_format($this->lineTotal[$index] ?? 0, 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $index }})" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="100%" class="text-center">
                                    <flux:text class="text-muted italic">Agregue productos a la compra usando el buscador.</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="rounded-lg bg-zinc-800 p-4 text-right space-y-1">
                <div><span class="font-medium">Subtotal Neto:</span> S/. {{ number_format($this->subtotal, 2) }}</div>
                <div><span class="font-medium">IGV (Impuesto):</span> S/. {{ number_format($this->igvTotal, 2) }}</div>
                <div class="text-amber-500 font-bold text-lg">TOTAL: S/. {{ number_format($this->total, 2) }}</div>
            </div>
        </flux:card>
    </div>
</div>