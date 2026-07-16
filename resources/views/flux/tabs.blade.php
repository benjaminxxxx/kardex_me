{{-- resources/views/components/flux/tabs.blade.php --}}
@props(['variant' => 'default'])

@php
    $classes = $variant === 'segmented'
        ? 'inline-flex p-1 rounded-lg bg-zinc-800/5 dark:bg-white/10 h-10 w-full'
        : 'flex gap-4 h-10 border-b border-zinc-800/10 dark:border-white/20';
@endphp

{{-- Usamos un data attribute para que el botón hijo pueda saber en qué variante está --}}
<div 
    data-variant="{{ $variant }}"
    {{ $attributes->class([$classes])->merge([
        'data-flux-tabs' => '',
        'role' => 'tablist'
    ]) }}
>
    {{ $slot }}
</div>