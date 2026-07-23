<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBranch extends Model
{
    protected $fillable = [
        'supplier_id',
        'type',
        'name',
        'is_main',
        'country',
        'state',
        'city',
        'address',
        'phone',
        'email',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
