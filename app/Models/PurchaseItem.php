<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'presentation_id',
        'quantity', 'quantity_base', 'unit_cost', 'unit_cost_base',
        'discount_percent', 'igv_percent', 'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_base' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    'unit_cost_base' => 'decimal:6',
        'discount_percent' => 'decimal:2',
        'igv_percent' => 'decimal:2',
        'line_total' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}