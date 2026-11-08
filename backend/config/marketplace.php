<?php

// backend/config/marketplace.php
// Parameter bisnis marketplace. Nilai default = usulan di docs/DECISIONS.md §1.
// Ubah lewat .env, jangan edit angka di sini.

return [

    // D-01: zona waktu bisnis
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    // D-04: batas waktu mitra merespons penawaran (detik)
    'mitra_response_seconds' => (int) env('MITRA_RESPONSE_SECONDS', 180),

    // D-05: maksimal mitra yang ditawari sebelum order dibatalkan
    'max_mitra_attempts' => (int) env('MAX_MITRA_ATTEMPTS', 3),

    // D-06: batas bayar setelah mitra accept (menit)
    'payment_expiry_minutes' => (int) env('PAYMENT_EXPIRY_MINUTES', 30),

    // D-07: auto-selesai jika customer tidak konfirmasi (jam)
    'auto_complete_hours' => (int) env('AUTO_COMPLETE_HOURS', 24),

    // D-03: slot harus mulai minimal sekian menit dari sekarang
    'min_lead_minutes' => (int) env('MIN_LEAD_MINUTES', 60),

    // D-15: true = mitra baru langsung approved (dev/demo)
    'mitra_auto_approve' => filter_var(env('MITRA_AUTO_APPROVE', true), FILTER_VALIDATE_BOOLEAN),

    // D-02: blok slot 2 jam, jam operasional 08.00-20.00
    'slots' => [
        ['start' => '08:00', 'end' => '10:00'],
        ['start' => '10:00', 'end' => '12:00'],
        ['start' => '12:00', 'end' => '14:00'],
        ['start' => '14:00', 'end' => '16:00'],
        ['start' => '16:00', 'end' => '18:00'],
        ['start' => '18:00', 'end' => '20:00'],
    ],

    // D-10: format order_code, contoh JR-7K2M9QXD (tanpa 0, O, 1, I)
    'order_code' => [
        'prefix' => 'JR-',
        'length' => 8,
        'alphabet' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    ],

    // D-13: throttle aksi guest, format "percobaan,menit"
    'guest_throttle' => env('GUEST_THROTTLE', '5,1'),

    // Midtrans (kunci HANYA dari .env, jangan ditulis di sini)
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    ],

];