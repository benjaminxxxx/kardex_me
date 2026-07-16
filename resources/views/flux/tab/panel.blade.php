{{-- resources/views/components/flux/tab/panel.blade.php --}}
@props(['name'])

<div 
    x-show="activeTab === '{{ $name }}'" 
    x-cloak 
    role="tabpanel"
    {{ $attributes->class(['mt-4 focus:outline-none']) }}
>
    {{ $slot }}
</div>