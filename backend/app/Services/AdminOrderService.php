<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\PaymentStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

// Pesanan dan pembayaran di Admin Dashboard. Controller tipis, logika di sini (B-1).
class AdminOrderService
{
    public function __construct(
        private OrderStatusService $status,
        private OrderExpiryService $expiry,
    ) {
    }

    public function orders(?string $status, ?string $q, int $perPage): LengthAwarePaginator
    {
        return Order::with(['service', 'mitra.user'])
            ->when($status, fn($x, $v) => $x->where('order_status', $v))
            ->when($q, fn($x, $v) => $x->where(
                fn($w) => $w->where('order_code', 'like', "%{$v}%")
                    ->orWhere('customer_name', 'like', "%{$v}%")
                    ->orWhere('customer_phone', 'like', "%{$v}%")
            ))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Detail satu pesanan. Lazy expiry (B-15): pesanan yang sudah kedaluwarsa dibereskan sebelum ditampilkan.
    public function find(int $id): Order
    {
        $order = $this->expiry->refresh(Order::findOrFail($id));

        return $order->load([
            'service',
            'propertySize',
            'addons',
            'mitra.user',
            'payments',
            'statusHistories.changedBy',
        ]);
    }

    /**
     * Paksa selesai (D-07): hanya kalau mitra sudah menandai selesai tapi customer belum konfirmasi.
     * Perubahan status tetap lewat OrderStatusService (B-4), jadi riwayat mencatat admin sebagai pelaku.
     */
    public function forceComplete(int $orderId, User $admin): Order
    {
        $order = Order::findOrFail($orderId);

        if ($order->order_status !== OrderStatus::MITRA_COMPLETED) {
            throw new ApiException(
                'Pesanan hanya bisa dipaksa selesai setelah mitra menandai pekerjaan selesai.',
                409
            );
        }

        return $this->status->transition(
            $order,
            OrderStatus::COMPLETED,
            $admin,
            'Dipaksa selesai oleh admin.',
            'admin'
        );
    }

    public function payments(?string $status, ?string $q, int $perPage): LengthAwarePaginator
    {
        return Payment::with('order')
            ->when($status, fn($x, $v) => $x->where('payment_status', $v))
            ->when($q, fn($x, $v) => $x->where(
                fn($w) => $w->where('gateway_order_id', 'like', "%{$v}%")
                    ->orWhereHas('order', fn($o) => $o->where('order_code', 'like', "%{$v}%"))
            ))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Tandai hasil refund manual (D-08):
     *  refund_pending -> refunded | refund_failed,  refund_failed -> refunded.
     * Urutan kunci: order dulu, baru payment (sama dengan webhook Midtrans nanti, supaya tidak deadlock).
     */
    public function markRefund(int $paymentId, string $to): Payment
    {
        $payment = Payment::findOrFail($paymentId);

        return DB::transaction(function () use ($payment, $to) {
            Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $dari = $locked->payment_status;
            $asalBoleh = match ($to) {
                PaymentStatus::REFUNDED => [PaymentStatus::REFUND_PENDING, PaymentStatus::REFUND_FAILED],
                PaymentStatus::REFUND_FAILED => [PaymentStatus::REFUND_PENDING],
                default => [],
            };

            if (!in_array($dari, $asalBoleh, true)) {
                throw new ApiException(
                    'Status pembayaran tidak bisa diubah dari "' . PaymentStatus::label($dari)
                    . '" ke "' . PaymentStatus::label($to) . '".',
                    409
                );
            }

            $locked->payment_status = $to;
            if ($to === PaymentStatus::REFUNDED) {
                $locked->refunded_at = now();
                $locked->refund_amount = $locked->refund_amount ?? $locked->amount;
            }
            $locked->save();

            return $locked;
        });
    }
}