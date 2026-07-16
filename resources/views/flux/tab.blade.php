{{-- resources/views/components/flux/tab.blade.php --}}
@aware(['variant' => 'default']) {{-- <-- Esto hereda el variant del padre (<flux:tabs>) --}}
@props(['name'])

@php
    // Clases del HTML para Variant: Segmented
    $segmentedClasses = "flex whitespace-nowrap flex-1 justify-center items-center gap-2 rounded-md data-selected:shadow-xs text-sm font-medium text-zinc-600 hover:text-zinc-800 dark:hover:text-white dark:text-white/70 data-selected:text-zinc-800 dark:data-selected:text-white data-selected:bg-white dark:data-selected:bg-white/20 [&[disabled]]:opacity-50 dark:[&[disabled]]:opacity-75 [&[disabled]]:cursor-default [&[disabled]]:pointer-events-none px-4";

    // Clases del HTML para Variant: Default (Normal)
    $defaultClasses = "flex whitespace-nowrap gap-2 items-center px-2 -mb-px border-b-[2px] border-transparent text-sm font-medium text-zinc-400 dark:text-white/50 data-selected:border-(--color-accent-content) data-selected:text-(--color-accent-content) hover:data-selected:text-(--color-accent-content) hover:text-zinc-800 dark:hover:text-white [&[disabled]]:opacity-50 dark:[&[disabled]]:opacity-75 [&[disabled]]:cursor-default [&[disabled]]:pointer-events-none";
@endphp

<button 
    type="button"
    role="tab"
    data-flux-tab="data-flux-tab"
    @click="activeTab = '{{ $name }}'"
    {{-- Atributos de estado manejados por Alpine --}}
    :aria-selected="activeTab === '{{ $name }}' ? 'true' : 'false'"
    :tabindex="activeTab === '{{ $name }}' ? '0' : '-1'"
    :data-selected="activeTab === '{{ $name }}' ? '' : null"
    :data-active="activeTab === '{{ $name }}' ? '' : null"
    
    {{ $attributes->class([
        $variant === 'segmented' ? $segmentedClasses : $defaultClasses
    ]) }}
>
    {{ $slot }}
</button>