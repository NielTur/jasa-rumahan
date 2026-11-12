<?php

namespace App\Support;

// Daftar status pesanan + tabel transisi (docs/API_CONTRACT.md 6.1).
class OrderStatus
{
    const PENDING = 'pending';
    const SEARCHING_MITRA = 'searching_mitra';
    const WAITING_PAYMENT = 'waiting_payment';
    const CONFIRMED = 'confirmed';
    const ON_THE_WAY = 'on_the_way';
    const ARRIVED = 'arrived';
    const IN_PROGRESS = 'in_progress';
    const MITRA_COMPLETED = 'mitra_completed';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    // Dari status X boleh pindah ke status apa saja. Selain ini ditolak (409).
    const TRANSITIONS = [
        self::PENDING => [self::SEARCHING_MITRA],
        self::SEARCHING_MITRA => [self::WAITING_PAYMENT, self::CANCELLED],
        self::WAITING_PAYMENT => [self::CONFIRMED, self::CANCELLED],
        self::CONFIRMED => [self::ON_THE_WAY, self::CANCELLED],
        self::ON_THE_WAY => [self::ARRIVED, self::CANCELLED],
        self::ARRIVED => [self::IN_PROGRESS, self::CANCELLED],
        self::IN_PROGRESS => [self::MITRA_COMPLETED],
        self::MITRA_COMPLETED => [self::COMPLETED],
        self::COMPLETED => [],
        self::CANCELLED => [],
    ];

    // Jadwal mitra dianggap terpakai sejak waiting_payment sampai completed/cancelled (G-08)
    const SCHEDULE_BLOCKING = [
        self::WAITING_PAYMENT,
        self::CONFIRMED,
        self::ON_THE_WAY,
        self::ARRIVED,
        self::IN_PROGRESS,
        self::MITRA_COMPLETED,
    ];

    // Turunan untuk tampilan customer (kontrak 6.2)
    public static function trackingStage(string $status): string
    {
        return match ($status) {
            self::PENDING, self::SEARCHING_MITRA => 'searching',
            self::WAITING_PAYMENT => 'waiting',
            self::CONFIRMED => 'confirmed',
            self::ON_THE_WAY, self::ARRIVED, self::IN_PROGRESS => 'working',
            self::MITRA_COMPLETED => 'awaiting_confirmation',
            self::COMPLETED => 'done',
            self::CANCELLED => 'cancelled',
            default => 'searching',
        };
    }

    // Label Bahasa Indonesia untuk Admin Dashboard
    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Baru dibuat',
            self::SEARCHING_MITRA => 'Mencari mitra',
            self::WAITING_PAYMENT => 'Menunggu pembayaran',
            self::CONFIRMED => 'Dikonfirmasi',
            self::ON_THE_WAY => 'Mitra menuju lokasi',
            self::ARRIVED => 'Mitra tiba',
            self::IN_PROGRESS => 'Sedang dikerjakan',
            self::MITRA_COMPLETED => 'Menunggu konfirmasi customer',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
            default => $status,
        };
    }
}