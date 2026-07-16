@props(['model'])

<flux:separator />

<div class="grid gap-3 text-sm md:grid-cols-2">
    <div class="flex items-start gap-2">
        <flux:icon name="user-plus" class="mt-0.5 size-4 text-zinc-400" />
        <div>
            <flux:text size="sm" class="text-zinc-500">Creado por</flux:text>
            <flux:text class="font-medium">
                {{ $model->created_by_name ?? $model->createdBy?->name ?? 'Sistema' }}
            </flux:text>
            <flux:text size="sm" class="text-zinc-400">
                {{ $model->created_at?->format('d/m/Y H:i') }}
            </flux:text>
        </div>
    </div>

    <div class="flex items-start gap-2">
        <flux:icon name="pencil" class="mt-0.5 size-4 text-zinc-400" />
        <div>
            <flux:text size="sm" class="text-zinc-500">Última edición por</flux:text>
            @if ($model->updated_by_name)
                <flux:text class="font-medium">{{ $model->updated_by_name }}</flux:text>
                <flux:text size="sm" class="text-zinc-400">
                    {{ $model->updated_at?->format('d/m/Y H:i') }}
                </flux:text>
            @else
                <flux:text class="font-medium text-zinc-400">Sin ediciones</flux:text>
            @endif
        </div>
    </div>
</div>