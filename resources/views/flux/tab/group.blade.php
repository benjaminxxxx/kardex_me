{{-- resources/views/components/flux/tab/group.blade.php --}}
@props(['selected'])

<div 
    x-data="{ activeTab: '{{ $selected }}' }" 
    {{ $attributes->class(['w-full']) }}
>
    {{ $slot }}
</div>