<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExplosiveFieldDistribution extends Model
{
    use HasAuditColumns;

    protected $fillable = [
        'dispatch_id',
        'mining_labor_id',
        'driller_employee_id',

        'drill_depth_feet','guide_length_feet',

        'fulminante_qty',
        'emulnor_qty',
        'mecha_lenta_qty',
        'guia_qty',
        'guia_aux_qty',
        'anfo_qty',
    ];

    protected $casts = [
        'drill_depth_feet' => 'integer',
        'fulminante_qty' => 'decimal:4',
        'emulnor_qty' => 'decimal:4',
        'mecha_lenta_qty' => 'decimal:4',
        'guia_qty' => 'decimal:4',
        'guia_aux_qty' => 'decimal:4',
        'anfo_qty' => 'decimal:4',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(
            ExplosiveFieldDispatch::class,
            'dispatch_id'
        );
    }

    public function miningLabor(): BelongsTo
    {
        return $this->belongsTo(
            MiningLabor::class,
            'mining_labor_id'
        );
    }

    public function driller(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'driller_employee_id'
        );
    }

    // ===== Columnas "de cabecera", tomadas del despacho padre =====

    public function getYearAttribute(): ?int
    {
        return $this->dispatch?->dispatch_date?->year;
    }

    public function getMonthNameAttribute(): ?string
    {
        return $this->dispatch?->dispatch_date
            ? ucfirst($this->dispatch->dispatch_date->translatedFormat('F'))
            : null;
    }

    public function getShiftLabelAttribute(): string
    {
        return $this->dispatch?->shift === 'day' ? 'DIA' : 'NOCHE';
    }

    public function getLaborCodeAttribute(): ?string
    {
        return $this->miningLabor?->code; // "MI (lugar o labor)"
    }

    public function getLaborTypeLabelAttribute(): ?string
    {
        return $this->miningLabor ? strtoupper($this->miningLabor->labor_type) : null;
    }

    public function getDrillerNameAttribute(): ?string
    {
        return $this->driller?->person?->display_name;
    }

    // ===== Columnas calculadas (conversión a presentación de compra) =====

    /**
     * Busca el factor de conversión de una presentación por nombre parcial
     * (ej. "caja", "cajita") del producto asignado a ese rol en el despacho.
     * Devuelve null si no existe esa presentación  evita inventar un número.
     */
    private function presentationFactor(string $productColumn, string $nameContains): ?float
    {
        $productId = $this->dispatch?->$productColumn;
        if (! $productId) return null;

        $presentacion = ProductPresentation::where('product_id', $productId)
            ->where('is_active', true)
            ->where('name', 'like', "%{$nameContains}%")
            ->first();

        return $presentacion ? (float) $presentacion->conversion_factor : null;
    }

    public function getCajasDinAttribute(): ?float
    {
        $factor = $this->presentationFactor('emulnor_product_id', 'caja');
        return $factor ? round((float) $this->emulnor_qty / $factor, 6) : null;
    }

    public function getCajasGuiaAttribute(): ?float
    {
        $factor = $this->presentationFactor('mecha_lenta_product_id', 'caja');
        return $factor ? round((float) $this->mecha_lenta_qty / $factor, 6) : null;
    }

    public function getCajitasFulAttribute(): ?float
    {
        $factor = $this->presentationFactor('fulminante_product_id', 'cajita');
        return $factor ? round((float) $this->fulminante_qty / $factor, 6) : null;
    }

    /**
     * NOTA: no existe en el esquema actual una presentación en "peso" (kg)
     * independiente del conteo de unidades. Este valor requiere que exista
     * una presentación llamada explícitamente "Kg" o similar en el producto
     * de dinamita; si no existe, se retorna null en vez de inventar un factor.
     * Confirmar con el cliente el factor real kg/unidad antes de confiar en esto.
     */
    public function getKgDinAttribute(): ?float
    {
        $factor = $this->presentationFactor('emulnor_product_id', 'kg');
        return $factor ? round((float) $this->emulnor_qty / $factor, 6) : null;
    }

    public function getPiesPerforadosAttribute(): float
    {
        // Confirmado en el Excel: fulminante × long_guia_pies (pies estándar por taladro)
        return (float) $this->fulminante_qty * (float) ($this->guide_length_feet ?? 5);
    }
}