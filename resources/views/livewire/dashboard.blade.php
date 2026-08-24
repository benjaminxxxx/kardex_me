<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- ===== Encabezado ===== --}}
    <div>
        <flux:heading size="xl">Panel de control</flux:heading>
        <flux:subheading>Sistema Kardex - Minera Española S.A.C.</flux:subheading>
    </div>

    {{-- ===== Tarjetas de resumen (Usuarios) ===== --}}
    <div class="grid auto-rows-min gap-4 md:grid-cols-4">

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:subheading>Total usuarios</flux:subheading>
                <flux:icon name="users" class="size-5 text-neutral-400" />
            </div>
            <div class="mt-2 text-2xl font-semibold">{{ $totalUsuarios }}</div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:subheading>Usuarios activos</flux:subheading>
                <flux:icon name="check-circle" class="size-5 text-emerald-500" />
            </div>
            <div class="mt-2 text-2xl font-semibold">{{ $usuariosActivos }}</div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:subheading>Nuevos este mes</flux:subheading>
                <flux:icon name="user-plus" class="size-5 text-blue-500" />
            </div>
            <div class="mt-2 text-2xl font-semibold">{{ $usuariosNuevosMes }}</div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:subheading>Roles definidos</flux:subheading>
                <flux:icon name="shield-check" class="size-5 text-purple-500" />
            </div>
            <div class="mt-2 text-2xl font-semibold">{{ $totalRoles }}</div>
        </flux:card>
    </div>

    {{-- ===== Accesos directos ===== --}}
    <flux:card>
        <flux:subheading class="mb-3">Accesos rápidos</flux:subheading>
        <div class="flex flex-wrap gap-2">
            @can(App\Constants\Permisos::EMPLEADOS_VER)
                <flux:button href="{{ route('employees.index') }}" icon="users">Empleados</flux:button>
            @endcan
            @can(App\Constants\Permisos::PROVEEDORES_VER)
                <flux:button href="{{ route('suppliers.index') }}" icon="truck">Proveedores</flux:button>
            @endcan
            @can(App\Constants\Permisos::COMPRAS_VER)
                <flux:button href="{{ route('purchases.index') }}" icon="shopping-bag">Compras</flux:button>
            @endcan
            @can(App\Constants\Permisos::PRODUCTOS_VER)
                <flux:button href="{{ route('products.index') }}" icon="archive-box">Productos</flux:button>
            @endcan
            @can(App\Constants\Permisos::MOVIMIENTOS_VER)
                <flux:button href="{{ route('stock-movements.index') }}" icon="arrows-up-down">Movimientos</flux:button>
            @endcan
            @can(App\Constants\Permisos::DESPACHOS_EXPLOSIVOS_VER)
                <flux:button href="{{ route('explosive-dispatches.index') }}" icon="fire">Despacho de explosivos</flux:button>
            @endcan
            @can(App\Constants\Permisos::LABORES_VER)
                <flux:button href="{{ route('zones.index') }}" icon="map-pin">Zonas / Labores</flux:button>
            @endcan
            @can(App\Constants\Permisos::KARDEX_VER)
                <flux:button href="{{ route('kardex.index') }}" icon="book-open">Kardex</flux:button>
            @endcan
            @can(App\Constants\Permisos::ROLES_VER)
                <flux:button href="{{ route('roles.index') }}" icon="shield-check">Roles</flux:button>
            @endcan
        </div>
    </flux:card>

    {{-- ===== Últimos usuarios + Roadmap de módulos ===== --}}
    <div class="grid gap-4 md:grid-cols-2">

        {{-- Últimos usuarios --}}
        <flux:card>
            <flux:subheading class="mb-3">Últimos usuarios registrados</flux:subheading>
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($ultimosUsuarios as $usuario)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <div class="font-medium text-sm">{{ $usuario->name }}</div>
                            <div class="text-xs dark:text-white/70">{{ $usuario->email }}</div>
                        </div>
                        <div class="text-xs text-accent">
                            {{ $usuario->created_at->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted py-2">Sin usuarios registrados.</p>
                @endforelse
            </div>
        </flux:card>

        {{-- Roadmap de módulos del sistema --}}
        <flux:card>
            <flux:subheading class="mb-3">Módulos del sistema</flux:subheading>
            <div class="space-y-2">
                @php
                    $modulos = [
                        ['nombre' => 'Usuarios', 'estado' => 'activo'],
                        ['nombre' => 'Roles y Permisos', 'estado' => 'activo'],
                        ['nombre' => 'Empleados', 'estado' => 'activo'],
                        ['nombre' => 'Proveedores', 'estado' => 'activo'],
                        ['nombre' => 'Compras', 'estado' => 'activo'],
                        ['nombre' => 'Productos', 'estado' => 'activo'],
                        ['nombre' => 'Movimientos', 'estado' => 'activo'],
                        ['nombre' => 'Despacho de explosivos', 'estado' => 'activo'],
                        ['nombre' => 'Zonas / Labores', 'estado' => 'activo'],
                        ['nombre' => 'Kardex', 'estado' => 'activo'],
                        ['nombre' => 'Ventas', 'estado' => 'pendiente'],
                        ['nombre' => 'Asistencia de empleados', 'estado' => 'pendiente'],
                    ];
                @endphp

                @foreach ($modulos as $modulo)
                    <div class="flex items-center justify-between text-sm">
                        <span>{{ $modulo['nombre'] }}</span>
                        @if ($modulo['estado'] === 'activo')
                            <flux:badge color="emerald" size="sm">Activo</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Próximamente</flux:badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
</div>