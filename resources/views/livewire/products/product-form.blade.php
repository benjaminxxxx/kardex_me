<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('products.index') }}">Productos</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $mode === 'edit' ? 'Editar' : 'Nuevo' }} producto</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar producto' : 'Nuevo producto' }}</flux:heading>

    <flux:tab.group :selected="$activeTab" wire:key="product-form-{{ $productId ?? 'new' }}-{{ $validationCount }}">
        <flux:tabs variant="segmented">
            <flux:tab name="general">Datos generales</flux:tab>
            <flux:tab name="presentations">Presentaciones</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="general">
            <flux:card class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="code" label="Código interno" placeholder="FULM-001" />
                    <flux:input wire:model="name" label="Nombre" class="md:col-span-1" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="chemicalName" label="Nombre químico (opcional)" placeholder="Emulnor" />
                    <flux:input wire:model="brand" label="Marca" />
                </div>

                



                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="barcode" label="Código de barras (opcional)" />
                    <flux:input wire:model="sunatProductCode" label="Código SUNAT de bien (opcional)"
                        placeholder="12131705" />
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:select wire:model="categoryId" label="Categoría" placeholder="Seleccionar...">
                        @foreach ($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">
                                {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="unitId" label="Unidad base (mínima)" placeholder="Seleccionar...">
                        @foreach ($this->units as $unit)
                            <flux:select.option value="{{ $unit->id }}">
                                {{ $unit->name }} ({{ $unit->alias ?: $unit->sunat_code }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="explosiveRoleId" label="Rol en armada de explosivos (opcional)"
                        placeholder="No aplica / no es ingrediente de armada">
                        @foreach ($this->explosiveRoles as $role)
                            <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" label="Observaciones" />
                <flux:checkbox wire:model="isActive" label="Producto activo" />
            </flux:card>
        </flux:tab.panel>

        <flux:tab.panel name="presentations">
            <flux:card class="space-y-6">
                <flux:text class="text-muted text-sm">
                    Registra cómo se compra este producto (caja, rollo, bolsa...) y su equivalencia en la unidad base.
                </flux:text>

                @foreach ($presentations as $index => $presentation)
                    <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <flux:input wire:model="presentations.{{ $index }}.name" label="Nombre de presentación"
                                placeholder="Caja, Rollo, Bolsa..." class="max-w-xs" />

                            @if (count($presentations) > 1)
                                <flux:button size="sm" variant="ghost" icon="trash"
                                    wire:click="removePresentation({{ $index }})" />
                            @endif
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:input type="number" step="0.0001"
                                wire:model="presentations.{{ $index }}.conversion_factor"
                                label="Equivalencia en unidad base" placeholder="Ej: 308" />

                            <flux:select wire:model="presentations.{{ $index }}.unit_id" label="Código SUNAT (opcional)"
                                placeholder="Sin código">
                                @foreach ($this->units as $unit)
                                    <flux:select.option value="{{ $unit->id }}">
                                        {{ $unit->name }} ({{ $unit->alias ?: $unit->sunat_code }})
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="flex gap-6">
                            <flux:checkbox wire:model="presentations.{{ $index }}.is_default_purchase"
                                label="Presentación por defecto en compras" />
                            <flux:checkbox wire:model="presentations.{{ $index }}.is_active" label="Activa" />
                        </div>
                    </flux:card>
                @endforeach

                <flux:button variant="ghost" icon="plus" wire:click="addPresentation">
                    Agregar otra presentación
                </flux:button>
            </flux:card>
        </flux:tab.panel>
    </flux:tab.group>

    <div class="flex justify-between">
        <flux:button variant="ghost" href="{{ route('products.index') }}">Cancelar</flux:button>
        <flux:button variant="primary" wire:click="save" icon="check">
            {{ $mode === 'edit' ? 'Guardar cambios' : 'Registrar producto' }}
        </flux:button>
    </div>
</div>