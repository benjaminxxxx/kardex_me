<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;

class Kardex extends Model
{
    use HasAuditColumns;

    protected $fillable = [
        'product_id',
        'year',
        'month',
        'costing_method',

        'opening_qty',
        'opening_unit_cost',
        'opening_total_cost',

        'total_entries_qty',
        'total_entries_cost',

        'total_exits_qty',
        'total_exits_cost',

        'closing_qty',
        'closing_unit_cost',
        'closing_total_cost',

        'status',
        'excel_path',
        'closed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',

        'opening_qty' => 'decimal:4',
        'opening_unit_cost' => 'decimal:6',
        'opening_total_cost' => 'decimal:4',

        'total_entries_qty' => 'decimal:4',
        'total_entries_cost' => 'decimal:4',

        'total_exits_qty' => 'decimal:4',
        'total_exits_cost' => 'decimal:4',

        'closing_qty' => 'decimal:4',
        'closing_unit_cost' => 'decimal:6',
        'closing_total_cost' => 'decimal:4',

        'closed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function movements()
    {
        return $this->hasMany(KardexMovement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function period(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}