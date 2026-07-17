@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Laravel Starter Kit" {{ $attributes }}>
        <x-slot name="logo" class="flex size-8 items-center justify-center">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Laravel Starter Kit" {{ $attributes }}>
        <x-slot name="logo" class="flex size-8 items-center justify-center">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
