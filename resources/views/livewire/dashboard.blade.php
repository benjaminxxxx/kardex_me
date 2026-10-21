<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- ===== Encabezado ===== --}}
    <div>
        <flux:heading size="xl">Panel de control</flux:heading>
        <flux:subheading>Sistema Kardex - Minera Española S.A.C. · {{ ucfirst(now()->translatedFormat('F Y')) }}</flux:subheading>
    </div>

    {{-- ===== Alertas ===== --}}
    @if (count($alerts))
        <div class="space-y-2">
            @foreach ($alerts as [$level, $message])
                <flux:callout :variant="$level === 'danger' ? 'danger' : 'warning'" icon="exclamation-triangle">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @endforeach
        </div>
    @endif

    {{-- ===== Indicadores ===== --}}
    <div class="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @isset($pendingDistribution)
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:subheading>Despachos por distribuir</flux:subheading>
                    <flux:icon name="fire" class="size-5 text-amber-500" />
                </div>
                <div class="mt-2 text-2xl font-semibold">{{ number_format($pendingDistribution) }}</div>
                <flux:text size="sm" class="text-muted">
                    {{ number_format($dispatchesMonth) }} despachos este mes
                    @if ($lastDispatchDate)
                        · último {{ \Carbon\Carbon::parse($lastDispatchDate)->format('d/m') }}
                    @endif
                </flux:text>
            </flux:card>
        @endisset

        @isset($purchasesMonth)
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:subheading>Compras del mes</flux:subheading>
                    <flux:icon name="shopping-bag" class="size-5 text-blue-500" />
                </div>
                <div class="mt-2 text-2xl font-semibold">{{ $purchasesMonth->sum('n') }}</div>
                <flux:text size="sm" class="text-muted">
                    @forelse ($purchasesMonth as $row)
                        {{ $row->currency }} {{ number_format($row->total, 2) }}@if (!$loop->last) · @endif
                    @empty
                        Sin compras registradas
                    @endforelse
                    @if ($deletedPurchasesMonth)
                        · {{ $deletedPurchasesMonth }} eliminada(s)
                    @endif
                </flux:text>
            </flux:card>
        @endisset

        @isset($openKardex)
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:subheading>Kárdex abiertos</flux:subheading>
                    <flux:icon name="book-open" class="size-5 text-purple-500" />
                </div>
                <div class="mt-2 text-2xl font-semibold">{{ $openKardex }}</div>
                <flux:text size="sm" class="text-muted">Pendientes de cierre</flux:text>
            </flux:card>
        @endisset

        @isset($stock)
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:subheading>Productos con stock</flux:subheading>
                    <flux:icon name="archive-box" class="size-5 text-emerald-500" />
                </div>
                <div class="mt-2 text-2xl font-semibold">{{ $stock->unique('code')->count() }}</div>
                <flux:text size="sm" class="text-muted">en {{ $stock->unique('warehouse')->count() }} almacén(es)</flux:text>
            </flux:card>
        @endisset
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- ===== Stock actual ===== --}}
        @isset($stock)
            <flux:card class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Stock actual</flux:heading>
                    @can(App\Constants\Permisos::MOVIMIENTOS_VER)
                        <flux:button size="sm" variant="ghost" href="{{ route('stock-movements.index') }}" wire:navigate>
                            Ver movimientos
                        </flux:button>
                    @endcan
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-96 overflow-auto">
                    @forelse ($stock as $row)
                        <div class="flex items-center justify-between gap-3 py-2 text-sm">
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ $row->name }}</div>
                                <div class="text-xs text-muted">{{ $row->code }} · {{ $row->warehouse }}</div>
                            </div>
                            <div @class(['font-semibold tabular-nums', 'text-red-600 dark:text-red-400' => $row->quantity < 0])>
                                {{ number_format($row->quantity, 2) }} {{ $row->unit }}
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-muted py-2">Sin stock registrado.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        @endisset

        {{-- ===== Actividad crítica reciente ===== --}}
        @isset($recentAudit)
            <flux:card class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Actividad crítica reciente</flux:heading>
                    <flux:button size="sm" variant="ghost" href="{{ route('audit.index') }}" wire:navigate>
                        Ver auditoría
                    </flux:button>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($recentAudit as $log)
                        <div class="py-2 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <flux:badge size="sm" :color="$log->severity === 'critical' ? 'red' : 'amber'">
                                    {{ $log->event_label }}
                                </flux:badge>
                                <span class="text-xs text-muted">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="mt-1">{{ $log->description }}</div>
                            <div class="text-xs text-muted">{{ $log->user_name ?? 'Sistema' }}</div>
                        </div>
                    @empty
                        <flux:text class="text-muted py-2">Sin eventos críticos registrados.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        @endisset
    </div>

    {{-- ===== Accesos directos ===== --}}
    <flux:card>
        <flux:subheading class="mb-3">Accesos rápidos</flux:subheading>
        <div class="flex flex-wrap gap-2">
            @can(App\Constants\Permisos::EXPLOSIVOS_DESPACHO_REGISTRAR)
                <flux:button href="{{ route('explosive-dispatches.create') }}" icon="plus" variant="primary">Nuevo despacho</flux:button>
            @endcan
            @can(App\Constants\Permisos::COMPRAS_GESTIONAR)
                <flux:button href="{{ route('purchases.create') }}" icon="plus">Nueva compra</flux:button>
            @endcan
            @can(App\Constants\Permisos::DESPACHOS_EXPLOSIVOS_VER)
                <flux:button href="{{ route('explosive-dispatches.index') }}" icon="fire">Despachos</flux:button>
            @endcan
            @can(App\Constants\Permisos::COMPRAS_VER)
                <flux:button href="{{ route('purchases.index') }}" icon="shopping-bag">Compras</flux:button>
            @endcan
            @can(App\Constants\Permisos::PRODUCTOS_VER)
                <flux:button href="{{ route('products.index') }}" icon="archive-box">Productos</flux:button>
            @endcan
            @can(App\Constants\Permisos::KARDEX_VER)
                <flux:button href="{{ route('kardex.index') }}" icon="book-open">Kardex</flux:button>
            @endcan
            @can(App\Constants\Permisos::EMPLEADOS_VER)
                <flux:button href="{{ route('employees.index') }}" icon="users">Empleados</flux:button>
            @endcan
        </div>
    </flux:card>
</div>
