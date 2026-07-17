
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
                <flux:button href="{{ route('employees.index') }}" icon="users">
                    Ver empleados
                </flux:button>
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
                                <div class="text-xs text-neutral-500">{{ $usuario->email }}</div>
                            </div>
                            <div class="text-xs text-neutral-400">
                                {{ $usuario->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500 py-2">Sin usuarios registrados.</p>
                    @endforelse
                </div>
            </div>

            {{-- Roadmap de módulos del sistema --}}
            <flux:card>
                <flux:subheading class="mb-3">Módulos del sistema</flux:subheading>
                <div class="space-y-2">
                    @php
                        $modulos = [
                            ['nombre' => 'Empleados', 'estado' => 'activo'],
                            ['nombre' => 'Kardex', 'estado' => 'pendiente'],
                            ['nombre' => 'Productos', 'estado' => 'pendiente'],
                            ['nombre' => 'Entradas', 'estado' => 'pendiente'],
                            ['nombre' => 'Salidas', 'estado' => 'pendiente'],
                            ['nombre' => 'Proveedores', 'estado' => 'pendiente'],
                            ['nombre' => 'Zonas', 'estado' => 'pendiente'],
                            ['nombre' => 'Roles', 'estado' => 'activo'],
                            ['nombre' => 'Permisos', 'estado' => 'activo'],
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