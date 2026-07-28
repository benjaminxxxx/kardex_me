<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['sunat_code', 'name', 'alias', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function getDisplayNameAttribute(): string
    {
        return $this->alias ?: $this->name;
    }
}
