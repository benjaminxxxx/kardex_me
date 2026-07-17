<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Configuración de apariencia</flux:heading>

    <x-settings.layout
        heading="Apariencia"
        subheading="Actualice la configuración de apariencia de su cuenta."
    >
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">Claro</flux:radio>
            <flux:radio value="dark" icon="moon">Oscuro</flux:radio>
            <flux:radio value="system" icon="computer-desktop">Sistema</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>