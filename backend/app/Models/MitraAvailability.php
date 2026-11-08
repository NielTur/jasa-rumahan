<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraAvailability extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'mitra_id',
        'day_of_week', // 0 = Minggu ... 6 = Sabtu (D-09)
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraProfile::class, 'mitra_id');
    }
}