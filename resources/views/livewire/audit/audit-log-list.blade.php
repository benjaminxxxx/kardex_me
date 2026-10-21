<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Sistema</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Auditoría</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div>
        <flux:heading size="xl">Auditoría</flux:heading>
        <flux:text class="text-muted mt-2">
            Registro de acciones críticas: eliminaciones, cambios de stock, accesos, permisos y configuración.
            Los registros no se pueden editar ni borrar.
        </flux:text>
    </div>

    {{-- ===== Filtros ===== --}}
    <div class="grid gap-4 md:grid-cols-5">
        <div class="md:col-span-2">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                placeholder="Buscar en descripción, motivo o usuario..." />
        </div>

        <flux:select wire:model.live="event">
            <flux:select.option value="">Todos los eventos</flux:select.option>
            @foreach ($events as $key => $label)
                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="severity">
            <flux:select.option value="">Toda severidad</flux:select.option>
            <flux:select.option value="critical">Crítico</flux:select.option>
            <flux:select.option value="warning">Advertencia</flux:select.option>
            <flux:select.option value="info">Informativo</flux:select.option>
        </flux:select>

        <div class="grid grid-cols-2 gap-2">
            <flux:input type="date" wire:model.live="dateFrom" aria-label="Desde" />
            <flux:input type="date" wire:model.live="dateTo" aria-label="Hasta" />
        </div>
    </div>

    @if ($search || $event || $severity || $dateFrom || $dateTo)
        <div class="flex items-center gap-2">
            <flux:text size="sm" class="text-muted">Filtros activos:</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="clearFilters">Limpiar</flux:button>
        </div>
    @endif

    <flux:table :paginate="$this->logs">
        <flux:table.columns>
            <flux:table.column>Fecha y hora</flux:table.column>
            <flux:table.column>Evento</flux:table.column>
            <flux:table.column>Descripción</flux:table.column>
            <flux:table.column>Usuario</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->logs as $log)
                <flux:table.row :key="$log->id">
                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                            <span class="text-muted text-xs">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="match ($log->severity) { 'critical' => 'red', 'warning' => 'amber', default => 'zinc' }">
                            {{ $log->event_label }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-normal max-w-md">
                        <div class="flex flex-col">
                            <span>{{ $log->description }}</span>
                            @if ($log->reason)
                                <span class="text-muted text-sm">Motivo: {{ \Illuminate\Support\Str::limit($log->reason, 120) }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span>{{ $log->user_name ?? '—' }}</span>
                            @if ($log->ip_address)
                                <span class="text-muted text-xs">{{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="view({{ $log->id }})">
                            Detalle
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="100%" class="text-center">
                        <flux:text class="text-muted">No hay registros de auditoría con estos filtros.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showDetail" class="md:w-[640px]">
        @if ($log = $this->viewing)
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $log->event_label }}</flux:heading>
                    <flux:text class="mt-1">{{ $log->description }}</flux:text>
                </div>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-muted">Fecha y hora</dt>
                    <dd>{{ $log->created_at->format('d/m/Y H:i:s') }}</dd>
                    <dt class="text-muted">Usuario</dt>
                    <dd>{{ $log->user_name ?? '—' }}</dd>
                    <dt class="text-muted">IP</dt>
                    <dd>{{ $log->ip_address ?? '—' }}</dd>
                    <dt class="text-muted">Registro afectado</dt>
                    <dd>{{ $log->auditable_type ? class_basename($log->auditable_type) . ' #' . $log->auditable_id : '—' }}</dd>
                </dl>

                @if ($log->reason)
                    <div>
                        <flux:heading size="sm">Motivo</flux:heading>
                        <flux:text class="mt-1 whitespace-pre-line">{{ $log->reason }}</flux:text>
                    </div>
                @endif

                @foreach (['old_values' => 'Datos anteriores', 'new_values' => 'Datos nuevos'] as $field => $title)
                    @if ($log->$field)
                        <div>
                            <flux:heading size="sm">{{ $title }}</flux:heading>
                            <pre class="mt-1 max-h-64 overflow-auto rounded-lg bg-zinc-100 dark:bg-zinc-800 p-3 text-xs">{{ json_encode($log->$field, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </flux:modal>
</div>
