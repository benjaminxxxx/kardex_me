<?php

namespace App\Models;

use App\Services\AuditLogger;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasAuditColumns;

    const UPDATED_AT = null;
    protected $fillable = [
        'direction',
        'product_id',
        'quantity',
        'movement_date',
        'warehouse_id',
        'source_type',
        'reason',
        'source_id',
        'purchase_item_id'
    ];

    protected static function booted(): void
    {
        // Los ajustes manuales no tienen documento de origen: se auditan al crearse
        static::created(function (StockMovement $mov) {
            if (!$mov->source_type) {
                AuditLogger::log(
                    'stock_movement.created',
                    sprintf('Ajuste manual de %s: %s u. del producto #%d', $mov->direction === 'in' ? 'entrada' : 'salida', (float) $mov->quantity, $mov->product_id),
                    $mov,
                    new: $mov->auditSnapshot(),
                    reason: $mov->reason,
                    severity: AuditLog::SEVERITY_WARNING,
                );
            }
        });

        // Toda eliminación de un movimiento queda registrada con la foto completa
        static::deleted(function (StockMovement $mov) {
            AuditLogger::log(
                'stock_movement.deleted',
                sprintf('Movimiento #%d eliminado (%s %s u. producto #%d)', $mov->id, $mov->direction, (float) $mov->quantity, $mov->product_id),
                $mov,
                old: $mov->auditSnapshot(),
                severity: AuditLog::SEVERITY_WARNING,
            );
        });
    }

    public function auditSnapshot(): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => (float) $this->quantity,
            'movement_date' => $this->movement_date?->toDateString(),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'purchase_item_id' => $this->purchase_item_id,
            'reason' => $this->reason,
            'created_by_name' => $this->created_by_name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    protected $casts = [
        'quantity' => 'decimal:4',
        'movement_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
    /**
     * Nombre legible del origen, según config/stock_movement_sources.php.
     * Si aparece un source_type nuevo que aún no se registró en el config,
     * se muestra el nombre corto de la clase en vez de romper la vista.
     */
    public function getSourceLabelAttribute(): string
    {
        if (!$this->source_type) {
            return $this->reason ?: 'Ajuste manual';
        }

        $settings = CompanySetting::current();

        // Despacho de explosivos
        if ($this->source_type === ExplosiveFieldDispatch::class) {

            if (
                $settings->reception_warehouse_id &&
                $this->warehouse_id == $settings->reception_warehouse_id
            ) {
                return $this->direction === 'in'
                    ? 'Devolución recibida'
                    : 'Devolución entregada';
            }

            return $this->direction === 'out'
                ? 'Despacho de explosivos'
                : 'Ingreso de explosivos';
        }

        // Transferencia entre almacenes
        if ($this->source_type === WarehouseTransfer::class) {
            return 'Transferencia entre almacenes';
        }

        $map = config('stock_movement_sources', []);

        return $map[$this->source_type] ?? class_basename($this->source_type);
    }
}