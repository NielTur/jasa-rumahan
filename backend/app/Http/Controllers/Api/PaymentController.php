<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderActionRequest;
use App\Models\Order;
use App\Services\MidtransPaymentService;
use App\Services\OrderAccessService;
use App\Services\OrderExpiryService;
use App\Support\ApiResponse;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private OrderAccessService $access,
        private OrderExpiryService $expiry,
        private MidtransPaymentService $payments,
    ) {
    }

    // POST /orders/{order_code}/payments
    public function store(OrderActionRequest $request, string $orderCode): JsonResponse
    {
        $order = $this->access->find($orderCode, $request->user('sanctum'), $request->input('phone'));
        $result = $this->payments->createSnap($order);

        return ApiResponse::success($result, 'Transaksi pembayaran berhasil dibuat.');
    }

    // GET /orders/{order_code}/payment
    public function show(OrderActionRequest $request, string $orderCode): JsonResponse
    {
        $order = $this->access->find($orderCode, $request->user('sanctum'), $request->input('phone'));
        $payment = $order->payments()->orderByDesc('id')->first();

        // Cek Status API sebelum lazy expiry agar settlement yang webhook-nya terlambat
        // tidak langsung dianggap expired oleh jam lokal.
        if ($payment && $payment->gateway === 'midtrans' && $payment->payment_status === 'pending') {
            $this->payments->syncFromStatusApi($payment);
        }

        $order = $this->expiry->refresh($order->fresh());
        $payment = $order->payments()->orderByDesc('id')->first();

        return ApiResponse::success([
            'order_code' => $order->order_code,
            'order_status' => $order->order_status,
            'tracking_stage' => OrderStatus::trackingStage($order->order_status),
            'payment' => $payment ? [
                'payment_status' => $payment->payment_status,
                'amount' => (int) $payment->amount,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'expired_at' => $payment->expired_at?->toIso8601String(),
                'refund_amount' => $payment->refund_amount,
            ] : null,
        ]);
    }
}
