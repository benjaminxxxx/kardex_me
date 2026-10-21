<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item>Actualizaciones</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Actualizaciones</flux:heading>
            <flux:text class="text-muted mt-2">Cambios y mejoras del sistema, ordenados por fecha.</flux:text>
        </div>

        <div class="w-full sm:w-64">
            <flux:select wire:model.live="module">
                <flux:select.option value="">Todos los módulos</flux:select.option>
                @foreach ($this->modules as $m)
                    <flux:select.option value="{{ $m }}">{{ $m }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="space-y-6">
        @forelse ($entries as $entry)
            <flux:card class="space-y-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <flux:heading size="lg">{{ $entry['title'] ?? 'Actualización' }}</flux:heading>
                    <flux:text class="text-muted">
                        {{ ucfirst(\Carbon\Carbon::parse($entry['date'])->translatedFormat('l j \d\e F \d\e Y')) }}
                    </flux:text>
                </div>

                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($entry['changes'] as $change)
                        @php $type = $types[$change['type'] ?? ''] ?? ['label' => ucfirst($change['type'] ?? 'Cambio'), 'color' => 'zinc', 'icon' => 'information-circle']; @endphp
                        <li class="flex gap-3 py-3">
                            <flux:icon :name="$type['icon']" variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge size="sm" :color="$type['color']">{{ $type['label'] }}</flux:badge>
                                    <span class="text-sm font-medium">{{ $change['module'] ?? 'General' }}</span>
                                </div>
                                <flux:text>{{ $change['description'] }}</flux:text>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </flux:card>
        @empty
            <flux:text class="text-muted">No hay actualizaciones registradas.</flux:text>
        @endforelse
    </div>
</div>
