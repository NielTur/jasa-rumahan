<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'password',
        'role',            // customer | mitra | admin
        'account_status',  // active | inactive | suspended
    ];

    protected $hidden = [
        'password',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    // Hanya ada kalau role = mitra
    public function mitraProfile(): HasOne
    {
        return $this->hasOne(MitraProfile::class, 'user_id');
    }

    // Pesanan sebagai customer terdaftar
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}