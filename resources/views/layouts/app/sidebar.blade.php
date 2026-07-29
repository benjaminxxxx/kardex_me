<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.header>
            <flux:sidebar.brand href="#" logo="{{ asset('images/logo.svg') }}"
                logo:dark="{{ asset('images/logo.svg') }}" name="Kardex" />

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>

            {{-- DOMINIO: KARDEX --}}
            <flux:sidebar.group expandable icon="clipboard-document-list" heading="Kardex" class="grid"
                :default-open="true">
                @can(App\Constants\Permisos::MOVIMIENTOS_VER)
                    <flux:sidebar.item icon="arrows-up-down" href="{{ route('stock-movements.index') }}"
                        :current="request()->routeIs('stock-movements.*')">
                        Movimientos
                    </flux:sidebar.item>
                @endcan

                @can(App\Constants\Permisos::DESPACHOS_EXPLOSIVOS_VER)
                    <flux:sidebar.item icon="fire" href="{{ route('explosive-dispatches.index') }}" wire:navigate>
                        Despachos
                    </flux:sidebar.item>
                    @can(App\Constants\Permisos::EXPLOSIVOS_DESPACHO_REGISTRAR)
                        <flux:sidebar.item icon="fire" href="{{ route('explosive-dispatches.create') }}" wire:navigate>
                            Despachar explosivos
                        </flux:sidebar.item>
                    @endcan

                    @can(App\Constants\Permisos::DISTRIBUCION_REPORTE_VER)
                        <flux:sidebar.item icon="queue-list" href="{{ route('explosive-distribution-report.index') }}"
                            wire:navigate>
                            Distribuciones de exp.
                        </flux:sidebar.item>
                    @endcan
                @endcan
                @can(App\Constants\Permisos::LABORES_VER)
                    <flux:sidebar.item icon="map-pin" href="{{ route('zones.index') }}"
                        :current="request()->routeIs('zones.*')">
                        Labores mineras
                    </flux:sidebar.item>
                @endcan
                @can(App\Constants\Permisos::KARDEX_VER)
                    <flux:sidebar.item icon="clipboard-document" href="{{ route('kardex.index') }}"
                        :current="request()->routeIs('kardex.index')">
                        Kardex
                    </flux:sidebar.item>
                @endcan
                @can(App\Constants\Permisos::KARDEX_GESTIONAR)
                    <flux:sidebar.item icon="clipboard-document" href="{{ route('kardex.wizard') }}"
                        :current="request()->routeIs('kardex.wizard')">
                        Registrar Kardex
                    </flux:sidebar.item>
                @endcan



            </flux:sidebar.group>

            {{-- DOMINIO: ALMACÉN / INVENTARIO --}}
            @canany([
                    App\Constants\Permisos::PRODUCTOS_VER,
                ])
                <flux:sidebar.group expandable icon="archive-box" heading="Almacén" class="grid">
                    @can(App\Constants\Permisos::PRODUCTOS_VER)
                        <flux:sidebar.item icon="cube" href="{{ route('products.index') }}"
                            :current="request()->routeIs('products.*')">
                            Productos
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany
            @canany([
                    App\Constants\Permisos::PROVEEDORES_VER,
                    App\Constants\Permisos::COMPRAS_VER,
                    App\Constants\Permisos::COMPRAS_GESTIONAR
                ])
                {{-- DOMINIO: COMPRAS --}}
                <flux:sidebar.group expandable icon="shopping-cart" heading="Compras" class="grid">
                    @can(App\Constants\Permisos::PROVEEDORES_VER)
                        <flux:sidebar.item icon="truck" href="{{ route('suppliers.index') }}"
                            :current="request()->routeIs('suppliers.*')">
                            Proveedores
                        </flux:sidebar.item>
                    @endcan
                    @can(App\Constants\Permisos::COMPRAS_VER)
                        <flux:sidebar.item icon="shopping-bag" href="{{ route('purchases.index') }}"
                            :current="request()->routeIs('purchases.index')">
                            Compras
                        </flux:sidebar.item>
                    @endcan
                    @can(App\Constants\Permisos::COMPRAS_GESTIONAR)
                        <flux:sidebar.item icon="shopping-bag" href="{{ route('purchases.create') }}"
                            :current="request()->routeIs('purchases.create')">
                            Registrar compra
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany
            @canany([
                    App\Constants\Permisos::EMPLEADOS_VER,
                ])
                {{-- DOMINIO: RECURSOS HUMANOS --}}
                <flux:sidebar.group expandable icon="users" heading="Recursos Humanos" class="grid">
                    @can(App\Constants\Permisos::EMPLEADOS_VER)
                        <flux:sidebar.item icon="user-group" href="{{ route('employees.index') }}"
                            :current="request()->routeIs('employees.*')">
                            Empleados
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany
            @canany([
                    App\Constants\Permisos::ROLES_VER,
                    App\Constants\Permisos::CONFIGURACION_VER,
                ])
                <flux:sidebar.group expandable icon="cog-6-tooth" heading="Sistema" class="grid">
                    {{-- - @can(App\Constants\Permisos::USUARIOS_VER)
                    <flux:navlist.item icon="users" href="{{ route('users.index') }}" wire:navigate>
                        Usuarios
                    </flux:navlist.item>
                    @endcan
                    --}}
                    @can(App\Constants\Permisos::ROLES_VER)
                        <flux:navlist.item icon="shield-check" href="{{ route('roles.index') }}" wire:navigate>
                            Roles
                        </flux:navlist.item>
                    @endcan
                    @can(App\Constants\Permisos::CONFIGURACION_VER)
                        <flux:navlist.item icon="cog-6-tooth" href="{{ route('settings.company') }}" wire:navigate>
                            Configuración
                        </flux:navlist.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany


        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        Configuración
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        Salir
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>