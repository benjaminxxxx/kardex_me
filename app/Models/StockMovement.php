<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    const UPDATED_AT = null;
    protected $fillable = [
        'direction',
        'product_id',
        'quantity',
        'movement_date',
        'warehouse_id',
        'source_type',
        'source_id',
        'purchase_item_id'
    ];

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
            return 'Ajuste manual';
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