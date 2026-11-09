<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertySize extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'property_type_id',
        'size_name',
        'min_area',
        'max_area',
        'is_active',
    ];

    protected $casts = [
        'min_area' => 'float',
        'max_area' => 'float',
        'is_active' => 'boolean',
    ];

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'property_size_id');
    }
}