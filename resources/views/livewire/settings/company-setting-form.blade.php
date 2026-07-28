<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Sistema</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Configuración</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Configuración general</flux:heading>
    <flux:text class="text-muted">Parámetros globales del sistema.</flux:text>

    <flux:card class="space-y-4">
        <flux:heading size="sm" class="text-muted uppercase tracking-wide">Datos de la empresa</flux:heading>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="companyName" label="Nombre comercial" placeholder="Minera La Española S.A.C." />
            <flux:input wire:model="ruc" label="RUC" placeholder="20123456789" maxlength="11" />
        </div>

        <flux:textarea wire:model="fiscalAddress" label="Dirección fiscal" />
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="sm" class="text-muted uppercase tracking-wide">Parámetros operativos</flux:heading>

        <flux:select wire:model="purchaseDefaultWarehouseId" label="Almacén por defecto para las compras">
            <flux:select.option value="">Seleccionar...</flux:select.option>
            @foreach ($this->warehouses as $wh)
                <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="mineDispatchWarehouseId" label="Almacén para salida a mina">
            <flux:select.option value="">Seleccionar...</flux:select.option>
            @foreach ($this->warehouses as $wh)
                <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="receptionWarehouseId" label="Almacén para materiales que sobraron">
            <flux:select.option value="">Seleccionar...</flux:select.option>
            @foreach ($this->warehouses as $wh)
                <flux:select.option value="{{ $wh->id }}">{{ $wh->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:text size="sm" class="text-muted">
            Este almacén se usará por defecto en el módulo de Despacho de explosivos, sin depender de su nombre actual.
        </flux:text>
    </flux:card>

    <div class="flex justify-end">
        <flux:button variant="primary" wire:click="save" icon="check">Guardar configuración</flux:button>
    </div>
</div>