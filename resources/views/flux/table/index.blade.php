@blaze(fold: true)

@props([
    'paginate' => null,
])

@php
$classes = Flux::classes()
    ->add('[:where(&)]:min-w-full table-fixed border-separate border-spacing-0 isolate')
    ->add('text-zinc-800')
    // We want whitespace-nowrap for the table, but not for modals and dropdowns...
    ->add('whitespace-nowrap [&_dialog]:whitespace-normal [&_[popover]]:whitespace-normal')
    ;

$containerClasses = Flux::classes()
    ->add('flex flex-col')
    ->add($attributes->pluck('container:class'))
    ;
@endphp

<div class="{{ $containerClasses }}">
    {{ $header ?? '' }}

    <div class="relative">
        <ui-table-scroll-area class="overflow-auto">
            <table {{ $attributes->class($classes) }} data-flux-table>
                {{ $slot }}
            </table>
        </ui-table-scroll-area>

        <?php if ($paginate): ?>
            {{-- Personalizado: capa de carga mientras se cambia de página --}}
            <div wire:loading.delay.flex wire:target="gotoPage, nextPage, previousPage"
                class="hidden absolute inset-0 z-10 items-center justify-center rounded-lg bg-white/60 dark:bg-zinc-900/60 backdrop-blur-[1px]"
                role="status">
                <div class="flex items-center gap-2 rounded-lg bg-white dark:bg-zinc-800 px-4 py-2 shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-700 text-sm text-zinc-600 dark:text-zinc-300">
                    <flux:icon.loading variant="mini" />
                    Cargando...
                </div>
            </div>
        <?php endif; ?>
    </div>

    {{ $footer ?? '' }}

    <?php if ($paginate): ?>
        <?php $paginationAttributes = Flux::attributesAfter('pagination:', $attributes, ['paginator' => $paginate, 'class' => 'shrink-0']); ?>
        <flux:pagination :attributes="$paginationAttributes" />
    <?php endif; ?>
</div>
