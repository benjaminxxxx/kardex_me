<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, HasAuditColumns;

    protected $fillable = [
        'code',
        'name',
        'chemical_name',
        'brand',
        'category_id',
        'unit_id',
        'barcode',
        'sunat_product_code',
        'notes',
        'is_active',
        'explosive_role_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
    public function explosiveRole()
    {
        return $this->belongsTo(ExplosiveRole::class);
    }
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }
}