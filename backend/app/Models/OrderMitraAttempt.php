<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMitraAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'mitra_id',       // mitra_profiles.id
        'attempt_number',
        'status',         // offered | accepted | rejected | expired
        'offered_at',
        'responded_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'offered_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraProfile::class, 'mitra_id');
    }
}