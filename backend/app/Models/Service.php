<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'service_name',
        'description',
        'duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    // Jasa dapat ditampilkan/dipesan hanya jika jasa dan kategorinya sama-sama aktif.
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('services.is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'service_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ServiceAddon::class, 'service_id');
    }

    // Mitra yang menyediakan jasa ini (pivot mitra_services)
    public function mitras(): BelongsToMany
    {
        return $this->belongsToMany(MitraProfile::class, 'mitra_services', 'service_id', 'mitra_id')
            ->withPivot('status');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'service_id');
    }
}