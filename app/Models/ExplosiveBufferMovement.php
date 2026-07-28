<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExplosiveBufferMovement extends Model
{
    public static function bufferBalance(int $productId): float
    {
        return (float) static::where('product_id', $productId)
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'credit' THEN quantity ELSE -quantity END), 0) as balance")
            ->value('balance');
    }
}
