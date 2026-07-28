<flux:modal wire:model.self="showModalTransfer" class="md:w-[480px]">
    @if ($product)
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Transferir entre almacenes</flux:heading>
                <flux:text class="mt-1 text-muted">{{ $product->name }}</flux:text>
            </div>

            <flux:select wire:model.live="fromWarehouseId" label="Almacén de origen">
                <flux:select.option value="">Seleccionar...</flux:select.option>
                @foreach ($this->warehouses as $wh)
                    <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->availableBreakdown !== null)
                <flux:text size="sm" class="text-muted">
                    Disponible en origen: {{ $this->availableBreakdown }}
                </flux:text>
            @endif

            <flux:select wire:model="toWarehouseId" label="Almacén de destino">
                <flux:select.option value="">Seleccionar...</flux:select.option>
                @foreach ($this->warehouses as $wh)
                    <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div>
                <flux:input.group>
                    <flux:select wire:model.live="presentationId" class="max-w-fit">
                        <flux:select.option value="">{{ $product->unit->alias ?: $product->unit->name }}
                        </flux:select.option>
                        @foreach ($this->activePresentations as $presentacion)
                            <flux:select.option value="{{ $presentacion->id }}">{{ $presentacion->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input type="number" step="0.0001" wire:model.live="quantity" placeholder="Cantidad" />
                </flux:input.group>

                @if ($this->quantityPreview)
                    <flux:text size="sm" class="text-muted mt-1">
                        {{ $this->quantityPreview }}
                    </flux:text>
                @endif
            </div>
            <flux:input type="date" wire:model="transferDate" label="Fecha de transferencia" />

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="close">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="save" icon="check">Transferir</flux:button>
            </div>
        </div>
    @endif
</flux:modal>