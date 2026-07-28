<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory, SoftDeletes, HasAuditColumns;

    protected $fillable = [
        'person_id',
        'employee_code',
        'hire_date',
        'termination_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /*
     * Relaciones preparadas para cuando existan sus migraciones.
     * Descomentar según se vayan creando las tablas.
     *
     * public function contracts(): HasMany
     * {
     *     return $this->hasMany(EmployeeContract::class);
     * }
     *
     * public function salaries(): HasMany
     * {
     *     return $this->hasMany(EmployeeSalary::class);
     * }
     *
     * public function positions(): HasMany
     * {
     *     return $this->hasMany(EmployeePosition::class);
     * }
     *
     * public function departments(): HasMany
     * {
     *     return $this->hasMany(EmployeeDepartment::class);
     * }
     *
     * public function workplaces(): HasMany
     * {
     *     return $this->hasMany(EmployeeWorkplace::class);
     * }
     *
     * public function bankAccounts(): HasMany
     * {
     *     return $this->hasMany(EmployeeBankAccount::class);
     * }
     *
     * public function pensionFunds(): HasMany
     * {
     *     return $this->hasMany(EmployeePensionFund::class);
     * }
     *
     * public function healthInsurances(): HasMany
     * {
     *     return $this->hasMany(EmployeeHealthInsurance::class);
     * }
     */
    public function explosiveDispatchesMade(): HasMany
    {
        return $this->hasMany(
            ExplosiveFieldDispatch::class,
            'dispatched_by_employee_id'
        );
    }

    public function explosiveDispatchesRequested(): HasMany
    {
        return $this->hasMany(
            ExplosiveFieldDispatch::class,
            'requested_by_employee_id'
        );
    }
    // ---------------------------------------------------------------
    // Scopes útiles
    // ---------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    public function getDisplayNameAttribute(): ?string
    {
        return $this->person?->display_name;
    }
}
