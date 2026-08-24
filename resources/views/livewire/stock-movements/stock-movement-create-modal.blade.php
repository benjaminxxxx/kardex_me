<flux:modal wire:model.self="show" class="md:w-[520px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $direction === 'in' ? 'Registrar entrada manual' : 'Registrar salida manual' }}
            </flux:heading>
            <flux:text class="mt-1">
                {{ $direction === 'in' ? 'Aumenta el stock en el almacén seleccionado.' : 'Descuenta el stock del almacén seleccionado.' }}
            </flux:text>
        </div>

        <flux:select wire:model="warehouseId" label="Almacén">
            <flux:select.option value="">-- Seleccionar almacén --</flux:select.option>
            @foreach ($this->warehouses as $wh)
                <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div>
            <flux:label>Producto</flux:label>
            <livewire:shared.entity-search-select
                entityType="product"
                fieldContext="manual-movement-product"
                :selectedId="$productId"
                :selectedLabel="$productLabel" :wire:key="'product-search-'.$key"
            />
            @error('productId')
                <span class="text-xs text-red-500 mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="quantity" type="number" step="0.0001" min="0.0001" label="Cantidad" placeholder="0.00" />
            <flux:input wire:model="movementDate" type="date" label="Fecha" />
        </div>

        <div>
            <livewire:shared.stock-movement-reason-select 
                :direction="$direction" 
                :reason="$reason" 
                :wire:key="'reason-select-'.$key"
            />
            @error('reason')
                <span class="text-xs text-red-500 mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex justify-between">
            <flux:button variant="ghost" wire:click="close">Cancelar</flux:button>
            <flux:button 
                variant="primary" 
                wire:click="save" 
                icon="check" 
                :color="$direction === 'in' ? 'green' : 'red'"
            >
                {{ $direction === 'in' ? 'Guardar entrada' : 'Guardar salida' }}
            </flux:button>
        </div>
    </div>
</flux:modal>