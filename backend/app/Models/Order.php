<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // order_status ada di $fillable HANYA supaya order baru bisa dibuat.
    // Mengubah status setelah itu wajib lewat OrderStatusService (B-4).
    protected $fillable = [
        'order_code',
        'customer_id',          // NULL = guest
        'customer_address_id',
        'mitra_id',             // mitra_profiles.id, NULL = masih dicari
        'service_id',
        'property_size_id',
        'scheduled_at',
        'duration_minutes',
        'customer_name',
        'customer_phone',
        'service_address',
        'address_notes',
        'service_price',
        'addons_total',
        'total_price',
        'order_status',
        'customer_note',
        'cancelled_at',
        'cancel_reason',
        'cancelled_by',         // customer | mitra | admin | system
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'service_price' => 'integer',
        'addons_total' => 'integer',
        'total_price' => 'integer',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraProfile::class, 'mitra_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function propertySize(): BelongsTo
    {
        return $this->belongsTo(PropertySize::class, 'property_size_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(OrderAddon::class, 'order_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OrderMitraAttempt::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }
}