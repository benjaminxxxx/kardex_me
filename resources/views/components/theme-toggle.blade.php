<flux:dropdown x-data align="end" {{ $attributes }}>
    <flux:button variant="subtle" square class="group" aria-label="Tema de color">
        <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
        <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
        <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
        <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
    </flux:button>

    <flux:menu>
        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Claro</flux:menu.item>
        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Oscuro</flux:menu.item>
        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">Sistema</flux:menu.item>
    </flux:menu>
</flux:dropdown>
