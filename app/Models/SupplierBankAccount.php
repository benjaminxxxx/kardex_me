<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBankAccount extends Model
{
    protected $fillable = [
        'supplier_id',
        'type',
        'bank_name',
        'account_number',
        'cci',
        'currency',
        'wallet_provider',
        'wallet_phone',
        'account_holder_name',
        'is_main',
        'notes',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
