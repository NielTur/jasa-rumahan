<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// PERHATIAN: id model ini = "mitra_id" di seluruh sistem. Bukan users.id (B-11).
class MitraProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'service_area',
        'verification_status', // pending | approved | rejected
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(MitraAvailability::class, 'mitra_id');
    }

    // Jasa yang dipilih mitra (pivot mitra_services, kolom status = active|inactive).
    // Saat attach/sync wajib isi status, contoh: attach($id, ['status' => 'active'])
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'mitra_services', 'mitra_id', 'service_id')
            ->withPivot('status');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'mitra_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OrderMitraAttempt::class, 'mitra_id');
    }
}