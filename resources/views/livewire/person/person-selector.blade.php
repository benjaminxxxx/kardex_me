{{-- livewire/shared/person-selector.blade.php --}}
<flux:modal wire:model.self="show" class="md:w-[640px]">
    <div class="space-y-6">

        @if ($justRegistered)
            {{-- ===== Modo confirmación tras registro exitoso ===== --}}
            <div>
                <flux:heading size="lg">Persona registrada</flux:heading>
                <flux:text class="mt-1">Confirma para continuar con esta persona.</flux:text>
            </div>

            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ $justRegistered['display_name'] }}</flux:callout.heading>
                <flux:callout.text>Documento: {{ $justRegistered['document_number'] }}</flux:callout.text>
            </flux:callout>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="close">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="acceptJustRegistered" icon="check">
                    Aceptar y continuar
                </flux:button>
            </div>

        @else
            {{-- ===== Modo búsqueda ===== --}}
            <div>
                <flux:heading size="lg">Seleccionar persona</flux:heading>
                <flux:text class="mt-1">Busca por nombre, apellido o documento.</flux:text>
            </div>

            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Ej: Juan Ramos, 45213897..."
                autofocus
            />

            {{-- El botón de agregar persona SIEMPRE está visible --}}
            <flux:button variant="ghost" icon="plus" wire:click="openRegistrar">
                No existe, registrar persona o entidad nueva
            </flux:button>

            @if ($search && mb_strlen($search) >= 2)
                <div class="max-h-72 divide-y divide-zinc-200 overflow-y-auto rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @forelse ($this->results as $person)
                        <div
                            wire:click="selectRow({{ $person->id }})"
                            class="flex cursor-pointer items-center justify-between p-3 transition
                                {{ $selectedPersonId === $person->id ? 'bg-blue-50 dark:bg-blue-950' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                        >
                            <div>
                                <flux:text class="font-medium">{{ $person->display_name }}</flux:text>
                                <flux:text size="sm" class="text-zinc-500">
                                    {{ $person->document_type }}: {{ $person->document_number }}
                                    @if ($person->mobile) · {{ $person->mobile }} @endif
                                </flux:text>
                            </div>

                            @if ($selectedPersonId === $person->id)
                                <flux:icon name="check-circle" class="size-5 text-blue-600" />
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <flux:text class="text-zinc-500">Sin resultados para "{{ $search }}"</flux:text>
                        </div>
                    @endforelse
                </div>
            @endif

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="close">Cerrar</flux:button>
                <flux:button
                    variant="primary"
                    wire:click="confirmSelection"
                    :disabled="!$selectedPersonId"
                >
                    Seleccionar
                </flux:button>
            </div>
        @endif
    </div>
</flux:modal>