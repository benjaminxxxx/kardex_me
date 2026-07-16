<flux:modal wire:model.self="show" class="md:w-[520px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Editar datos laborales</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $displayName }}</flux:text>
        </div>

        <flux:input wire:model="hireDate" type="date" label="Fecha de ingreso" />

        <flux:select wire:model.live="status" label="Estado">
            <flux:select.option value="active">Activo</flux:select.option>
            <flux:select.option value="inactive">Inactivo</flux:select.option>
            <flux:select.option value="suspended">Suspendido</flux:select.option>
            <flux:select.option value="terminated">Cesado</flux:select.option>
        </flux:select>

        @if ($status === 'terminated')
            <flux:input wire:model="terminationDate" type="date" label="Fecha de cese" />
        @endif

        <flux:textarea wire:model="notes" label="Observaciones" />

        <div class="flex justify-between">
            <flux:button variant="ghost" wire:click="close">Cancelar</flux:button>
            <flux:button variant="primary" wire:click="save" icon="check">Guardar cambios</flux:button>
        </div>
    </div>
</flux:modal>