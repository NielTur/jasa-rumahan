<?php

namespace App\Support;

// Daftar status pembayaran (docs desain 11.3) + label Bahasa Indonesia untuk Admin Dashboard.
class PaymentStatus
{
    const PENDING = 'pending';
    const PAID = 'paid';
    const FAILED = 'failed';
    const EXPIRED = 'expired';
    const REFUND_PENDING = 'refund_pending';
    const REFUNDED = 'refunded';
    const REFUND_FAILED = 'refund_failed';

    const ALL = [
        self::PENDING,
        self::PAID,
        self::FAILED,
        self::EXPIRED,
        self::REFUND_PENDING,
        self::REFUNDED,
        self::REFUND_FAILED,
    ];

    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Menunggu bayar',
            self::PAID => 'Lunas',
            self::FAILED => 'Gagal',
            self::EXPIRED => 'Kedaluwarsa',
            self::REFUND_PENDING => 'Refund menunggu',
            self::REFUNDED => 'Sudah direfund',
            self::REFUND_FAILED => 'Refund gagal',
            default => $status,
        };
    }
}