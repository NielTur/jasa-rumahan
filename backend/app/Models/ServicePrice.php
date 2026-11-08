<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
{
    protected $fillable = [
        'service_id',
        'property_size_id',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer', // integer rupiah di API (D-17)
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function propertySize(): BelongsTo
    {
        return $this->belongsTo(PropertySize::class, 'property_size_id');
    }
}