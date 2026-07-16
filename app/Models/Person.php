<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes, HasAuditColumns;

    /**
     * Nombre de la tabla.
     */
    protected $table = 'persons';

    /**
     * Asignación masiva.
     */
    protected $fillable = [
        'code',
        'type',
        'document_type',
        'document_number',
        'names',
        'paternal_last_name',
        'maternal_last_name',
        'company_name',
        'display_name',
        'legal_name',
        'birth_date',
        'gender',
        'marital_status',
        'mobile',
        'phone',
        'email',
        'country',
        'state',
        'city',
        'district',
        'postal_code',
        'address',
        'notes',
        'is_active',
    ];

    /**
     * Conversión de tipos.
     */
    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Usuario asociado a la persona.
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
}
