<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\SoftDeletes;

class MiningLabor extends Model
{
    use SoftDeletes, HasAuditColumns;

    protected $fillable = [
        'code', 'labor_type', 'vein_name', 'level_number',
        'direction', 'status', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level_number' => 'integer',
    ];

    public const PREFIXES = [
        'tajo' => 'TJ',
        'subnivel' => 'S/N',
        'galeria' => 'GAL',
        'estocada' => 'EST',
        'crucero' => 'CX',
        'chimenea' => 'CH',
        'pique' => 'PQ',
        'buzon' => 'B/C',
    ];

    public static function buildCode(string $laborType, int $levelNumber, string $veinName): string
    {
        $prefix = self::PREFIXES[$laborType] ?? '??';
        return trim("{$prefix} {$levelNumber} " . strtoupper($veinName));
    }

    protected static function booted(): void
    {
        static::saving(function (MiningLabor $labor) {
            $labor->code = self::buildCode($labor->labor_type, $labor->level_number, $labor->vein_name);
        });
    }

    public static function veinSuggestions(): array
    {
        return static::query()
            ->distinct()
            ->orderBy('vein_name')
            ->pluck('vein_name')
            ->toArray();
    }
}
