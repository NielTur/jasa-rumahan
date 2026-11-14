<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// Integrasi Snap dan sinkronisasi status pembayaran Midtrans (B-7).
class MidtransPaymentService
{
    public function __construct(
        private MidtransGateway $gateway,
        private OrderStatusService $orderStatus,
        private OrderExpiryService $expiry,
    ) {
    }

    public function createSnap(Order $order): array
    {
        if ($order->order_status !== OrderStatus::WAITING_PAYMENT) {
            throw new ApiException('Pesanan belum berada pada tahap menunggu pembayaran.', 409);
        }

        // Memproses batas bayar terlebih dahulu; pesanan yang sudah kedaluwarsa tidak boleh dibuatkan Snap.
        $order = $this->expiry->refresh($order);
        if ($order->order_status !== OrderStatus::WAITING_PAYMENT) {
            throw new ApiException('Batas pembayaran pesanan sudah habis.', 409);
        }

        if (blank(config('marketplace.midtrans.server_key'))) {
            throw new ApiException('Konfigurasi pembayaran belum tersedia.', 503);
        }

        return DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->order_status !== OrderStatus::WAITING_PAYMENT) {
                throw new ApiException('Pesanan tidak sedang menunggu pembayaran.', 409);
            }

            $deadline = $this->paymentDeadline($locked);
            if ($deadline->lte(now())) {
                throw new ApiException('Batas pembayaran pesanan sudah habis.', 409);
            }

            $latest = $locked->payments()->where('gateway', 'midtrans')->orderByDesc('id')->first();
            if ($latest && $latest->payment_status === 'pending') {
                if ($latest->snap_token) {
                    return [
                        'snap_token' => $latest->snap_token,
                        'redirect_url' => $latest->gateway_payload['redirect_url'] ?? null,
                        'expired_at' => $latest->expired_at?->toIso8601String(),
                        'gateway_order_id' => $latest->gateway_order_id,
                    ];
                }

                throw new ApiException('Transaksi pembayaran sedang diproses. Coba beberapa saat lagi.', 409);
            }

            $attempt = (int) $locked->payments()->where('gateway', 'midtrans')->count() + 1;
            $gatewayOrderId = $locked->order_code . '-' . $attempt;
            $amount = (int) $locked->total_price;
            $expiredAt = $deadline;
            $duration = (int) now()->diffInMinutes($expiredAt, false);
            // Midtrans mensyaratkan expiry Snap minimal 5 menit.
            if ($duration < 5) {
                throw new ApiException('Sisa waktu pembayaran kurang dari 5 menit. Pesanan akan segera kedaluwarsa.', 409);
            }

            $payment = Payment::create([
                'order_id' => $locked->id,
                'gateway' => 'midtrans',
                'gateway_order_id' => $gatewayOrderId,
                'amount' => $amount,
                'payment_status' => 'pending',
                'expired_at' => $expiredAt,
            ]);

            $parameters = [
                'transaction_details' => [
                    'order_id' => $gatewayOrderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $locked->customer_name,
                    'phone' => $locked->customer_phone,
                ],
                'expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'minute',
                    'duration' => $duration,
                ],
            ];

            try {
                $snap = $this->gateway->createSnapTransaction($parameters);
            } catch (Throwable $e) {
                Log::error('Gagal membuat transaksi Snap Midtrans.', [
                    'gateway_order_id' => $gatewayOrderId,
                    'exception' => $e->getMessage(),
                ]);
                throw new ApiException('Transaksi pembayaran gagal dibuat. Silakan coba kembali.', 502);
            }

            if (empty($snap->token) || empty($snap->redirect_url)) {
                throw new ApiException('Midtrans tidak mengembalikan token pembayaran yang valid.', 502);
            }

            $payment->update([
                'snap_token' => $snap->token,
                'gateway_payload' => ['redirect_url' => $snap->redirect_url],
            ]);

            return [
                'snap_token' => $payment->snap_token,
                'redirect_url' => $snap->redirect_url,
                'expired_at' => $payment->expired_at?->toIso8601String(),
                'gateway_order_id' => $payment->gateway_order_id,
            ];
        });
    }

    // Webhook dan Status API memakai jalur pemrosesan yang sama.
    public function processNotification(array $payload): void
    {
        $gatewayOrderId = (string) ($payload['order_id'] ?? '');
        if ($gatewayOrderId === '') {
            return;
        }

        DB::transaction(function () use ($payload, $gatewayOrderId) {
            $payment = Payment::where('gateway_order_id', $gatewayOrderId)->lockForUpdate()->first();
            if (!$payment) {
                return;
            }

            $order = Order::whereKey($payment->order_id)->lockForUpdate()->first();
            if (!$order) {
                return;
            }

            $grossAmount = (string) ($payload['gross_amount'] ?? '');
            if (!$this->sameAmount($grossAmount, $payment->amount)) {
                Log::warning('Nominal notifikasi Midtrans tidak cocok.', [
                    'gateway_order_id' => $gatewayOrderId,
                    'received_amount' => $grossAmount,
                    'expected_amount' => $payment->amount,
                ]);
                $payment->gateway_payload = $payload;
                $payment->save();
                return;
            }

            $newStatus = $this->mapStatus(
                (string) ($payload['transaction_status'] ?? ''),
                (string) ($payload['fraud_status'] ?? ''),
                (string) ($payload['status_code'] ?? '')
            );

            $payment->transaction_id = $payload['transaction_id'] ?? $payment->transaction_id;
            $payment->payment_method = $payload['payment_type'] ?? $payment->payment_method;
            $payment->gateway_payload = $payload;

            if ($newStatus !== null && $this->canAdvance($payment->payment_status, $newStatus)) {
                $payment->payment_status = $newStatus;
                if ($newStatus === 'paid') {
                    $payment->paid_at = $payment->paid_at ?? now();
                }

                // Pembayaran yang masuk setelah pesanan dibatalkan perlu ditindaklanjuti sebagai refund.
                if ($newStatus === 'paid' && $order->order_status === OrderStatus::CANCELLED) {
                    $payment->payment_status = 'refund_pending';
                    $payment->refund_amount = $payment->amount;
                }

                $payment->save();

                if ($payment->payment_status === 'paid' && $order->order_status === OrderStatus::WAITING_PAYMENT) {
                    $this->orderStatus->transition(
                        $order,
                        OrderStatus::CONFIRMED,
                        null,
                        'Pembayaran Midtrans berhasil.',
                        'system'
                    );
                }
            } else {
                // Tetap simpan payload notifikasi valid walaupun statusnya duplikat/out-of-order.
                $payment->save();
            }
        });
    }

    public function syncFromStatusApi(Payment $payment): void
    {
        if ($payment->gateway !== 'midtrans' || $payment->payment_status !== 'pending') {
            return;
        }

        try {
            $status = $this->gateway->fetchTransactionStatus($payment->gateway_order_id);
            $payload = is_object($status) ? (array) $status : (array) $status;
            $this->processNotification($payload);
        } catch (Throwable $e) {
            // GET status tetap harus tersedia walaupun Status API sedang tidak dapat dijangkau.
            Log::warning('Gagal menyinkronkan status pembayaran dari Midtrans.', [
                'gateway_order_id' => $payment->gateway_order_id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function isValidSignature(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('marketplace.midtrans.server_key');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '' || $serverKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, $signature);
    }

    private function mapStatus(string $transactionStatus, string $fraudStatus, string $statusCode): ?string
    {
        if (in_array($transactionStatus, ['settlement', 'capture'], true)
            && ($statusCode !== '200' || ($fraudStatus !== '' && $fraudStatus !== 'accept'))) {
            return 'pending';
        }

        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => $fraudStatus === 'accept' ? 'paid' : 'pending',
            'pending' => 'pending',
            'deny', 'cancel', 'failure' => 'failed',
            'expire' => 'expired',
            'refund', 'partial_refund' => 'refunded',
            default => null,
        };
    }

    private function canAdvance(string $current, string $incoming): bool
    {
        if ($current === $incoming) {
            return true;
        }

        // Status final dan status refund tidak boleh diturunkan oleh notifikasi lama.
        if (in_array($current, ['paid', 'refund_pending', 'refund_failed'], true)) {
            return $incoming === 'refunded' && in_array($current, ['paid', 'refund_pending', 'refund_failed'], true);
        }
        if ($current === 'refunded') {
            return false;
        }
        if (in_array($current, ['expired', 'failed'], true)) {
            return $incoming === 'paid' || $incoming === 'refunded';
        }

        return $current === 'pending';
    }

    private function sameAmount(string $grossAmount, int|float|string $expected): bool
    {
        if (!is_numeric($grossAmount)) {
            return false;
        }

        return number_format((float) $grossAmount, 2, '.', '') === number_format((float) $expected, 2, '.', '');
    }

    private function paymentDeadline(Order $order): Carbon
    {
        $entered = OrderStatusHistory::where('order_id', $order->id)
            ->where('new_status', OrderStatus::WAITING_PAYMENT)
            ->orderByDesc('id')
            ->value('created_at');

        $start = $entered ? Carbon::parse($entered) : $order->updated_at;

        return $start->copy()->addMinutes((int) config('marketplace.payment_expiry_minutes'));
    }
}
