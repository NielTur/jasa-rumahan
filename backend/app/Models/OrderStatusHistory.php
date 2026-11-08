<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    // Tabel ini cuma punya created_at, tidak ada updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'changed_by_user_id', // NULL = sistem
        'old_status',
        'new_status',
        'note',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}