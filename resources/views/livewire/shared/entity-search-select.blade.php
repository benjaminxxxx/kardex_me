<div x-data="{ highlighted: 0 }">
    @if ($searching)
        <div class="relative">
            <flux:input
                wire:model.live.debounce.200ms="search"
                placeholder="Buscar..."
                autocomplete="off"
                x-on:input="highlighted = 0"
                x-on:keydown.arrow-down.prevent="highlighted = Math.min(highlighted + 1, ($refs.resultsList?.children.length ?? 1) - 1)"
                x-on:keydown.arrow-up.prevent="highlighted = Math.max(highlighted - 1, 0)"
                x-on:keydown.enter.prevent="$refs.resultsList?.children[highlighted]?.click()"
            />

            @if (mb_strlen($search) >= 2)
                <div x-ref="resultsList"
                    class="absolute z-20 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    @forelse ($this->results as $i => $result)
                        <div
                            wire:click="select({{ $result->id }}, '{{ addslashes($result->label) }}')"
                            :class="highlighted === {{ $i }} ? 'bg-blue-50 dark:bg-blue-950' : ''"
                            class="cursor-pointer px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        >
                            {{ $result->label }}
                        </div>
                    @empty
                        <div class="px-3 py-2 text-sm text-zinc-500">Sin resultados</div>
                    @endforelse
                </div>
            @endif
        </div>
    @else
        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
            <span class="text-sm font-medium truncate">{{ $selectedLabel }}</span>
            <button type="button" wire:click="clear" class="text-zinc-400 hover:text-zinc-600 flex-shrink-0 ml-2">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif
</div>