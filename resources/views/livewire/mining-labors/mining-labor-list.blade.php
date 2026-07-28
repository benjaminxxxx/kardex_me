<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Kardex</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Labores</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Labores mineras</flux:heading>
            <flux:text class="mt-2">Administra los frentes de trabajo donde se destina el explosivo.</flux:text>
        </div>
        @can(App\Constants\Permisos::LABORES_GESTIONAR)
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                Nueva labor
            </flux:button>
        @endcan
    </div>

    {{-- ===== Filtros ===== --}}
    <x-flex class="justify-between flex-wrap gap-4">
        <x-flex class="">
            <div>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Buscar por código..." class="w-auto" />
            </div>

            <flux:select wire:model.live="laborType" placeholder="Tipo de labor" class="w-auto">
                <flux:select.option value="">Todos los tipos</flux:select.option>
                <flux:select.option value="tajo">Tajo</flux:select.option>
                <flux:select.option value="subnivel">Subnivel</flux:select.option>
                <flux:select.option value="galeria">Galería</flux:select.option>
                <flux:select.option value="estocada">Estocada</flux:select.option>
                <flux:select.option value="crucero">Crucero</flux:select.option>
                <flux:select.option value="chimenea">Chimenea</flux:select.option>
                <flux:select.option value="pique">Piqué</flux:select.option>
                <flux:select.option value="buzon">Buzón</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="veinName" placeholder="Veta" class="w-auto">
                <flux:select.option value="">Todas las vetas</flux:select.option>
                @foreach ($this->veinOptions as $vein)
                    <flux:select.option value="{{ $vein }}">{{ $vein }}</flux:select.option>
                @endforeach
            </flux:select>

            <div>
                <flux:input type="number" wire:model.live.debounce.300ms="levelNumber" placeholder="Nivel (ej: 138)"
                    class="w-auto" />
            </div>
        </x-flex>

        <flux:field variant="inline">
            <flux:label>Ver eliminados</flux:label>
            <flux:switch wire:model.live="showTrashed" />
        </flux:field>
    </x-flex>

    @if ($showTrashed)
        <flux:callout variant="warning" icon="information-circle">
            <flux:callout.text>Mostrando labores eliminadas. Puedes restaurarlas o eliminarlas permanentemente.
            </flux:callout.text>
        </flux:callout>
    @endif

    @if ($search || $laborType || $veinName || $levelNumber)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-zinc-500">Filtros activos:</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">Limpiar</flux:button>
        </div>
    @endif

    <flux:table :paginate="$labors">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection"
                wire:click="sort('code')">
                Código
            </flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'level_number'" :direction="$sortDirection"
                wire:click="sort('level_number')">
                Nivel
            </flux:table.column>
            <flux:table.column>Veta</flux:table.column>
            <flux:table.column>Dirección</flux:table.column>
            <flux:table.column>
                {{ $showTrashed ? 'Fecha de eliminación' : 'Estado' }}
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($labors as $labor)
                <flux:table.row :key="$labor->id">
                    <flux:table.cell variant="strong" class="font-mono">
                        {{ $labor->code }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ ucfirst($labor->labor_type) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>{{ $labor->level_number }}</flux:table.cell>
                    <flux:table.cell>{{ $labor->vein_name }}</flux:table.cell>
                    <flux:table.cell>{{ $labor->direction ? ucfirst($labor->direction) : '—' }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $labor->deleted_at?->format('d/m/Y H:i') }}
                            </flux:text>
                        @else
                            @php
                                $colors = ['active' => 'green', 'exhausted' => 'red', 'paused' => 'yellow'];
                                $labels = ['active' => 'Activa', 'exhausted' => 'Agotada', 'paused' => 'Pausada'];
                            @endphp
                            <flux:badge size="sm" :color="$colors[$labor->status]">
                                {{ $labels[$labor->status] }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($showTrashed)
                            @can(App\Constants\Permisos::LABORES_GESTIONAR)
                                <div class="flex gap-2 justify-end">
                                    <flux:button size="sm" variant="ghost" icon="arrow-path" wire:confirm="¿Restaurar esta labor?"
                                        wire:click="restoreLabor({{ $labor->id }})">
                                        Restaurar
                                    </flux:button>
                                    <flux:button size="sm" variant="danger" icon="trash"
                                        wire:confirm="¿Eliminar PERMANENTEMENTE esta labor? Esta acción no se puede deshacer. Solo es posible si nunca tuvo salidas registradas."
                                        wire:click="forceDeleteLabor({{ $labor->id }})">
                                        Eliminar definitivo
                                    </flux:button>
                                </div>
                            @endcan
                        @else
                            @can(App\Constants\Permisos::LABORES_GESTIONAR)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil" wire:click="openEdit({{ $labor->id }})">
                                            Editar
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:confirm="¿Eliminar esta labor? Podrás restaurarla después."
                                            wire:click="deleteLabor({{ $labor->id }})">
                                            Eliminar
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endcan
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-zinc-500">No existen labores registradas con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:mining-labors.mining-labor-form />
</div>