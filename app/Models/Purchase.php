<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes, HasAuditColumns;

    protected $fillable = [
        'supplier_id', 'warehouse_id', 'currency', 'exchange_rate',
        'document_type', 'document_number', 'document_date', 'due_date',
        'payment_method', 'subtotal_neto', 'igv_total', 'total', 'notes',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal_neto' => 'decimal:4',
        'igv_total' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}