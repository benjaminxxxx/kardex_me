<flux:modal wire:model.self="show" flyout variant="floating" class="md:w-lg">
    @if ($product)
        <div class="space-y-6">

            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="lg">{{ $product->name }}</flux:heading>
                    <flux:text class="mt-1 text-muted">Código: {{ $product->code }}</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    @if ($product->explosiveRole)
                        <flux:badge size="sm" color="amber" icon="fire">{{ $product->explosiveRole->name }}</flux:badge>
                    @endif
                    <flux:badge size="sm" :color="$product->is_active ? 'green' : 'zinc'">
                        {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                    </flux:badge>
                </div>
            </div>

            {{-- ===================== DATOS GENERALES ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-muted uppercase tracking-wide">
                    Información general
                </flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:text size="sm" class="text-muted">Categoría</flux:text>
                        <flux:text class="font-medium">{{ $product->category->name }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-muted">Unidad base</flux:text>
                        <flux:text class="font-medium">{{ $product->unit->name }}
                            ({{ $product->unit->alias ?: $product->unit->sunat_code }})</flux:text>
                    </div>

                    @if ($product->chemical_name)
                        <div>
                            <flux:text size="sm" class="text-muted">Nombre químico</flux:text>
                            <flux:text class="font-medium">{{ $product->chemical_name }}</flux:text>
                        </div>
                    @endif

                    @if ($product->brand)
                        <div>
                            <flux:text size="sm" class="text-muted">Marca</flux:text>
                            <flux:text class="font-medium">{{ $product->brand }}</flux:text>
                        </div>
                    @endif

                    @if ($product->barcode)
                        <div>
                            <flux:text size="sm" class="text-muted">Código de barras</flux:text>
                            <flux:text class="font-medium">{{ $product->barcode }}</flux:text>
                        </div>
                    @endif

                    @if ($product->sunat_product_code)
                        <div>
                            <flux:text size="sm" class="text-muted">Código SUNAT</flux:text>
                            <flux:text class="font-medium">{{ $product->sunat_product_code }}</flux:text>
                        </div>
                    @endif

                    @if ($product->notes)
                        <div class="md:col-span-2">
                            <flux:text size="sm" class="text-muted">Observaciones</flux:text>
                            <flux:text class="font-medium">{{ $product->notes }}</flux:text>
                        </div>
                    @endif

                    @if ($product->explosiveRole)
                        <div>
                            <flux:text size="sm" class="text-muted">Rol en armada</flux:text>
                            <flux:text class="font-medium">{{ $product->explosiveRole->name }}</flux:text>
                        </div>
                    @endif
                </div>

                <x-audit-trail :model="$product" />
            </flux:card>

            {{-- ===================== PRESENTACIONES ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-muted uppercase tracking-wide">
                    Presentaciones de compra
                </flux:heading>

                @forelse ($product->presentations as $presentation)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">{{ $presentation->name }}</flux:text>
                            <div class="flex items-center gap-2">
                                @if ($presentation->is_default_purchase)
                                    <flux:badge size="sm" color="blue">Por defecto</flux:badge>
                                @endif
                                <flux:badge size="sm" :color="$presentation->is_active ? 'green' : 'zinc'">
                                    {{ $presentation->is_active ? 'Activa' : 'Inactiva' }}
                                </flux:badge>
                            </div>
                        </div>
                        <flux:text size="sm" class="text-muted mt-1">
                            1 {{ $presentation->name }} = {{ $presentation->conversion_factor }}
                            {{ $product->unit->alias ?: $product->unit->name }}
                            @if ($presentation->unit)
                                · SUNAT: {{ $presentation->unit->sunat_code }}
                            @endif
                        </flux:text>
                    </div>
                @empty
                    <flux:text class="text-muted">Sin presentaciones registradas.</flux:text>
                @endforelse
            </flux:card>

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="close">Cerrar</flux:button>
            </div>
        </div>
    @endif
</flux:modal>