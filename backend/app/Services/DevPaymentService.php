<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

// KHUSUS DEVELOPMENT: menandai pesanan sudah dibayar tanpa Midtrans, supaya alur mitra bisa
// diuji dan dijadikan cadangan demo (DECISIONS 4). Di luar APP_ENV=local dibalas 404.
class DevPaymentService
{
    public function __construct(private OrderStatusService $status)
    {
    }

    public function simulatePaid(string $orderCode): Order
    {
        // Pagar utama: di luar local endpoint ini seolah tidak ada
        if (!app()->environment('local')) {
            throw new ApiException('Data tidak ditemukan.', 404);
        }

        return DB::transaction(function () use ($orderCode) {
            $order = Order::where('order_code', strtoupper(trim($orderCode)))->lockForUpdate()->first();

            if (!$order) {
                throw new ApiException('Pesanan tidak ditemukan.', 404);
            }
            if ($order->order_status !== OrderStatus::WAITING_PAYMENT) {
                throw new ApiException('Pesanan tidak sedang menunggu pembayaran.', 409);
            }

            // "-DEV1" sengaja beda dari pola {order_code}-{n} (D-11) supaya tidak tabrakan
            // dengan percobaan bayar Midtrans asli di P4.
            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'simulator',
                'gateway_order_id' => $order->order_code . '-DEV1',
                'amount' => $order->total_price, // integer rupiah, sama dengan total di server (B-3)
                'payment_method' => 'simulator',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'gateway_payload' => ['simulated' => true],
            ]);

            return $this->status->transition(
                $order,
                OrderStatus::CONFIRMED,
                null,
                'Pembayaran disimulasikan (khusus dev).',
                'system'
            );
        });
    }
}