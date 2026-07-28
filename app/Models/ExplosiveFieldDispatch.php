<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExplosiveFieldDispatch extends Model
{
    use HasAuditColumns;

    public const SHIFT_DAY = 'day';
    public const SHIFT_NIGHT = 'night';

    public const STATUS_PENDING_DISTRIBUTION = 'pending_distribution';
    public const STATUS_DISTRIBUTED = 'distributed';

    protected $fillable = [
        'dispatch_date',
        'shift',
        'dispatched_by_employee_id',
        'requested_by_employee_id',

        'fulminante_qty',
        'emulnor_qty',
        'mecha_lenta_qty',
        'guia_qty',
        'guia_aux_qty',
        'anfo_qty',

        'status',
        'notes',
    ];

    protected $casts = [
        'dispatch_date' => 'date',

        'fulminante_qty' => 'decimal:4',
        'emulnor_qty' => 'decimal:4',
        'mecha_lenta_qty' => 'decimal:4',
        'guia_qty' => 'decimal:4',
        'guia_aux_qty' => 'decimal:4',
        'anfo_qty' => 'decimal:4',
    ];

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'dispatched_by_employee_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    public function isPendingDistribution(): bool
    {
        return $this->status === self::STATUS_PENDING_DISTRIBUTION;
    }

    public function isDistributed(): bool
    {
        return $this->status === self::STATUS_DISTRIBUTED;
    }
    public function distributions(){
        return $this->hasMany(ExplosiveFieldDistribution::class,'dispatch_id');
    }
}