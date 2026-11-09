<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\RateLimiter;

// Siapa boleh melihat pesanan: pemilik (login) atau guest dengan order_code + phone (D-13).
// Tidak cocok -> 404 generik (B-9). Percobaan GAGAL dibatasi per IP + kode.
class OrderAccessService
{
    public function find(string $orderCode, ?User $user, ?string $phone): Order
    {
        $code = strtoupper(trim($orderCode));
        $order = Order::where('order_code', $code)->first();

        // 1. Pemilik yang login
        if ($order && $user && $user->role === 'customer' && (int) $order->customer_id === (int) $user->id) {
            return $order;
        }

        // 2. Guest: kode + telepon. Cek kunci throttle dulu.
        [$max, $minutes] = array_map('intval', explode(',', config('marketplace.guest_throttle')));
        $key = 'guest-order:' . request()->ip() . '|' . $code;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new ApiException('Terlalu banyak percobaan. Coba lagi sebentar.', 429);
        }

        $normalized = PhoneNormalizer::normalize($phone);

        if ($order && $normalized && hash_equals((string) $order->customer_phone, $normalized)) {
            return $order;
        }

        RateLimiter::hit($key, $minutes * 60);

        throw new ApiException('Pesanan tidak ditemukan.', 404);
    }
}