<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    <style>
        :root {
            --primary-brand: #3D441E;
            --primary-light: #5A6633;
            --accent-gold: #D4A574;
            --accent-yellow: #FCD34D;
            --neutral-50: #F9FAFB;
            --neutral-100: #F3F4F6;
            --neutral-900: #111827;
            --neutral-950: #030712;
        }

        .brand-gradient {
            background: linear-gradient(135deg, var(--primary-brand) 0%, var(--primary-light) 100%);
        }

        .text-brand {
            color: var(--primary-brand);
        }

        .accent-underline {
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-yellow));
            height: 3px;
        }
    </style>
</head>

<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-4 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">

        <!-- Panel Izquierdo - Branding -->
        <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800"
            style="
            background-image: url('{{ asset('images/mining-bg.webp') }}');
            background-size: cover;
            background-position: center;
        ">
            <!-- Overlays -->
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-black/60 via-black/10 to-black/70"></div>
            <div class="absolute inset-0 brand-gradient opacity-20"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 text-white h-full">

                <!-- Encabezado con Logo -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center flex-shrink-0">
                        <img src="{{ asset('images/logo.svg') }}" alt="MinKardex" class="h-14 w-14 object-contain" />
                    </div>

                    <div class="flex flex-col justify-center">
                        <h1 class="text-3xl font-bold tracking-tight leading-none">
                            MinKardex
                        </h1>
                        <p class="text-sm text-gray-300 mt-1">
                            Sistema inteligente de insumos
                        </p>
                    </div>
                </div>

                <!-- Contenido Principal -->
                <div class="space-y-10">
                    <!-- Headline -->
                    <div>
                        <h2 class="text-5xl font-bold mb-6 leading-tight tracking-tight">
                            Gestión Inteligente de Insumos
                        </h2>

                        <div class="accent-underline mb-6 w-16"></div>

                        <p class="text-lg text-gray-200 leading-relaxed">
                            Control total de tu kardex minero con tecnología moderna y confiable. Optimiza operaciones y
                            maximiza rentabilidad.
                        </p>
                    </div>

                    <!-- Features -->
                    <div class="space-y-5">
                        <div class="flex gap-4 items-start group cursor-pointer">
                            <div
                                class="w-2 h-2 rounded-full bg-gradient-to-br from-[#D4A574] to-[#FCD34D] mt-3 flex-shrink-0 group-hover:scale-125 transition-transform">
                            </div>
                            <p class="text-gray-200 text-base leading-relaxed group-hover:text-white transition-colors">
                                Registro en tiempo real de movimientos de insumos
                            </p>
                        </div>

                        <div class="flex gap-4 items-start group cursor-pointer">
                            <div
                                class="w-2 h-2 rounded-full bg-gradient-to-br from-[#D4A574] to-[#FCD34D] mt-3 flex-shrink-0 group-hover:scale-125 transition-transform">
                            </div>
                            <p class="text-gray-200 text-base leading-relaxed group-hover:text-white transition-colors">
                                Reportes detallados, análisis y trazabilidad completa
                            </p>
                        </div>

                        <div class="flex gap-4 items-start group cursor-pointer">
                            <div
                                class="w-2 h-2 rounded-full bg-gradient-to-br from-[#D4A574] to-[#FCD34D] mt-3 flex-shrink-0 group-hover:scale-125 transition-transform">
                            </div>
                            <p class="text-gray-200 text-base leading-relaxed group-hover:text-white transition-colors">
                                Control de acceso basado en roles y departamentos
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pie de página -->
                <div class="border-t border-white/10 pt-6">
                    <p class="text-sm text-gray-300 italic leading-relaxed">
                        <span class="text-[#D4A574] font-semibold">"La precisión en el control"</span> de insumos es el
                        corazón de la operación eficiente.
                    </p>
                </div>
            </div>

        </div>

        <!-- Panel Derecho - Formulario -->
        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-8 sm:w-[420px]">

                <!-- Logo Mobile -->
                <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-3 font-medium lg:hidden"
                    wire:navigate>
                    <div class="flex h-16 w-16 items-center justify-center rounded-lg">
                        <img src="{{ asset('images/logo.svg') }}" alt="MinKardex"
                            class="h-full w-full object-contain" />
                    </div>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <!-- Contenido dinámico -->
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