<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800"
            style="
            background-image: url('{{ asset('images/mining-bg.webp') }}');
            background-size: cover;
            background-position: center;
        ">
            <div class="absolute inset-0 bg-black/50"></div>

            <div class="absolute inset-0 bg-gradient-to-br from-black/60 via-black/10 to-black/70"></div>


            <div class="relative z-10 flex flex-col justify-between p-12 text-white h-full">

                <!-- Logo -->
                <div>
                    
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#d4a574] to-[#a67c52] flex items-center justify-center">
                            <x-app-logo-icon class="me-2 h-7 fill-current text-white" />
                        </div>

                        <span class="text-2xl font-bold">
                            MinKardex
                        </span>
                    </div>

                    <p class="text-sm text-gray-300 ml-13">
                        Sistema de gestión de insumos
                    </p>
                </div>

                <!-- Contenido -->
                <div class="space-y-8">

                    <div>
                        <h1 class="text-4xl font-bold mb-4 leading-tight">
                            Gestión Inteligente de Insumos
                        </h1>

                        <p class="text-xl text-gray-300">
                            Control total de tu kardex minero con tecnología moderna y confiable
                        </p>
                    </div>

                    <div class="space-y-4">

                        <div class="flex gap-4 items-start">
                            <div class="w-1 h-1 rounded-full bg-[#d4a574] mt-2 flex-shrink-0"></div>
                            <p class="text-gray-300">Registro en tiempo real de movimientos</p>
                        </div>

                        <div class="flex gap-4 items-start">
                            <div class="w-1 h-1 rounded-full bg-[#d4a574] mt-2 flex-shrink-0"></div>
                            <p class="text-gray-300">Reportes detallados y análisis</p>
                        </div>

                        <div class="flex gap-4 items-start">
                            <div class="w-1 h-1 rounded-full bg-[#d4a574] mt-2 flex-shrink-0"></div>
                            <p class="text-gray-300">Trazabilidad completa de insumos</p>
                        </div>

                    </div>

                </div>

                <div>
                    <p class="text-sm text-gray-400 italic">
                        "La precisión en el control de insumos es el corazón de la operación eficiente"
                    </p>
                </div>
            </div>

        </div>
        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden"
                    wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </div>

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>