<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuditColumns
{
    protected static function bootHasAuditColumns(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->created_by_name = Auth::user()->person?->display_name ?? Auth::user()->email;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
                $model->updated_by_name = Auth::user()->person?->display_name ?? Auth::user()->email;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check() && method_exists($model, 'trashed')) {
                // Se guarda antes del soft delete real
                $model->deleted_by = Auth::id();
                $model->deleted_by_name = Auth::user()->person?->display_name ?? Auth::user()->email;
                $model->saveQuietly(); // guarda sin disparar updating de nuevo
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}