<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sizes(): HasMany
    {
        return $this->hasMany(PropertySize::class, 'property_type_id');
    }
}