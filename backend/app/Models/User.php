<?php

namespace App\Models;

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
}