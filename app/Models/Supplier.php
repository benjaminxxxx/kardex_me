<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, HasAuditColumns;

    protected $fillable = [
        'person_id', 'supplier_code', 'status', 'notes',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    // Acceso al portal, si existe-reutiliza la relación Person->User que ya tienes
    public function user(): ?User
    {
        return $this->person->user;
    }

     public function branches(): HasMany
    {
        return $this->hasMany(SupplierBranch::class);
    }

    public function fiscalAddress(): HasOne
    {
        return $this->hasOne(SupplierBranch::class)->where('type', 'fiscal');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    public function mainBankAccount(): HasOne
    {
        return $this->hasOne(SupplierBankAccount::class)->where('is_main', true);
    }
}
