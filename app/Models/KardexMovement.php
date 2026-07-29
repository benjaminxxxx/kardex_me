<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexMovement extends Model
{
    protected $fillable = [
        'kardex_id',

        // Documento
        'document_type',
        'document_series',
        'document_number',
        'operation_type',

        // Referencia al movimiento real
        'stock_movement_id',

        // Movimiento
        'movement_date',
        'direction',
        'source_label',

        // Entradas
        'entry_qty',
        'entry_unit_cost',
        'entry_total_cost',

        // Salidas
        'exit_qty',
        'exit_unit_cost',
        'exit_total_cost',

        // Saldos
        'balance_qty',
        'balance_unit_cost',
        'balance_total_cost',
    ];

    protected $casts = [
        'movement_date' => 'date',

        'entry_qty' => 'decimal:4',
        'entry_unit_cost' => 'decimal:6',
        'entry_total_cost' => 'decimal:4',

        'exit_qty' => 'decimal:4',
        'exit_unit_cost' => 'decimal:6',
        'exit_total_cost' => 'decimal:4',

        'balance_qty' => 'decimal:4',
        'balance_unit_cost' => 'decimal:6',
        'balance_total_cost' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function kardex()
    {
        return $this->belongsTo(Kardex::class);
    }

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEntry(): bool
    {
        return $this->direction === 'in';
    }

    public function isExit(): bool
    {
        return $this->direction === 'out';
    }

    public function document(): string
    {
        return trim(implode('-', array_filter([
            $this->document_type,
            $this->document_series,
            $this->document_number,
        ])));
    }
}