<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_order_id',  // {order_code}-{n} (D-11)
        'transaction_id',
        'snap_token',
        'gateway_payload',   // payload mentah notifikasi Midtrans (B-7)
        'amount',
        'payment_method',
        'payment_status',
        'paid_at',
        'expired_at',
        'refund_amount',
        'refunded_at',
    ];

    protected $casts = [
        'gateway_payload' => 'array',
        'amount' => 'integer',
        'refund_amount' => 'integer',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}