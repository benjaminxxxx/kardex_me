<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasAuditColumns;

    protected $fillable = [
        'company_name',
        'ruc',
        'fiscal_address',
        'purchase_default_warehouse_id',
        'mine_dispatch_warehouse_id',
        'reception_warehouse_id',
        'restrict_distribution_to_requester',
    ];
    protected $casts = [
        'restrict_distribution_to_requester' => 'boolean',
    ];
    public function mineDispatchWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'mine_dispatch_warehouse_id');
    }
    public function receptionWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'reception_warehouse_id');
    }
    /**
     * Siempre existe una sola fila. La crea si no existe, la retorna si ya existe.
     * Nunca uses CompanySetting::create() directamente en otro lugar.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
