<?php

namespace App\Livewire\Changelog;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Historial de cambios del sistema. Se alimenta de resources/data/changelog.json:
 * cada actualización agrega una entrada nueva al inicio del archivo.
 */
#[Title('Actualizaciones')]
class ChangelogList extends Component
{
    public const TYPES = [
        'nuevo' => ['label' => 'Nuevo', 'color' => 'emerald', 'icon' => 'sparkles'],
        'mejora' => ['label' => 'Mejora', 'color' => 'blue', 'icon' => 'arrow-trending-up'],
        'correccion' => ['label' => 'Corrección', 'color' => 'amber', 'icon' => 'wrench-screwdriver'],
        'seguridad' => ['label' => 'Seguridad', 'color' => 'red', 'icon' => 'shield-check'],
    ];

    public string $module = '';

    public static function entries(): array
    {
        $path = resource_path('data/changelog.json');
        $entries = is_file($path) ? json_decode(file_get_contents($path), true) : [];

        return collect($entries ?: [])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    #[Computed]
    public function modules(): array
    {
        return collect(self::entries())
            ->flatMap(fn($e) => array_column($e['changes'] ?? [], 'module'))
            ->unique()->sort()->values()->all();
    }

    public function render()
    {
        $entries = collect(self::entries())
            ->map(function ($entry) {
                $entry['changes'] = collect($entry['changes'] ?? [])
                    ->when($this->module, fn($c) => $c->where('module', $this->module))
                    ->values()->all();
                return $entry;
            })
            ->filter(fn($e) => count($e['changes']))
            ->values();

        return view('livewire.changelog.changelog-list', [
            'entries' => $entries,
            'types' => self::TYPES,
        ]);
    }
}
