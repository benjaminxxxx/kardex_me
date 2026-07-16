{{-- livewire/employees/employee-wizard.blade.php --}}
<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('employees.index') }}">Empleados</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Nuevo empleado</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Nuevo empleado</flux:heading>

    @if ($step === 1)
        <flux:card class="space-y-4">
            <flux:text class="text-zinc-500">Esperando selección de persona...</flux:text>
            <flux:button variant="primary" wire:click="$dispatch('open-person-selector', { context: 'employee-wizard' })">
                Abrir buscador
            </flux:button>
            @error('personId') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:card>
    @endif

    @if ($step === 2)
        <flux:card class="space-y-6">
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ $personDisplayName }}</flux:callout.heading>
                <flux:callout.text>Documento: {{ $personDocumentNumber }}</flux:callout.text>
                <x-slot name="actions">
                    <flux:button size="sm" variant="ghost" wire:click="changePerson">Cambiar</flux:button>
                </x-slot>
            </flux:callout>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="hireDate" type="date" label="Fecha de ingreso" />
                <flux:select wire:model="status" label="Estado">
                    <flux:select.option value="active">Activo</flux:select.option>
                    <flux:select.option value="inactive">Inactivo</flux:select.option>
                    <flux:select.option value="suspended">Suspendido</flux:select.option>
                </flux:select>
            </div>
            <flux:textarea wire:model="notes" label="Observaciones" />

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="save" icon="check">Guardar empleado</flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Submódulos: siempre presentes en el DOM, controlados por eventos --}}
    <livewire:person.person-selector />
    <livewire:person.person-registrar />
</div>