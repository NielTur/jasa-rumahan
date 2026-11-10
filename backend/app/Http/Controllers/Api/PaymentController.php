<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderActionRequest;
use App\Services\OrderAccessService;
use App\Support\ApiResponse;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;

// Pembayaran pesanan. P4a: hanya cek status. P4b menambah pembuatan transaksi Snap di sini.
class PaymentController extends Controller
{
    public function __construct(private OrderAccessService $access)
    {
    }

    // GET /orders/{order_code}/payment?phone=08... (phone hanya untuk guest)
    // Flutter memanggil ini berulang setelah WebView pembayaran ditutup (F-8).
    public function show(OrderActionRequest $request, string $orderCode): JsonResponse
    {
        $order = $this->access->find($orderCode, $request->user('sanctum'), $request->input('phone'));
        $payment = $order->payments()->orderByDesc('id')->first();

        return ApiResponse::success([
            'order_code' => $order->order_code,
            'order_status' => $order->order_status,
            'tracking_stage' => OrderStatus::trackingStage($order->order_status),
            // null = belum ada pembayaran (customer belum membuka halaman bayar). Bukan error.
            'payment' => $payment ? [
                'payment_status' => $payment->payment_status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'expired_at' => $payment->expired_at?->toIso8601String(),
                'refund_amount' => $payment->refund_amount,
            ] : null,
        ]);
    }
}