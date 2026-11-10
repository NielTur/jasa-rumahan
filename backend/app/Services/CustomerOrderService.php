<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

// Aksi customer atau guest pada pesanannya: batalkan dan konfirmasi selesai.
// Status berubah HANYA lewat OrderStatusService (B-4). Pengecekan awal di sini hanya untuk
// pesan error yang ramah, keputusan akhir tetap di transition() setelah baris order dikunci.
class CustomerOrderService
{
    public function __construct(
        private OrderStatusService $status,
        private MatchingService $matching,
    ) {
    }

    /**
     * Batalkan pesanan. Boleh dari searching_mitra sampai arrived (tabel transisi kontrak 6.1).
     * Kalau pembayaran sudah paid, uang ditandai perlu dikembalikan (D-08): refund_pending.
     */
    public function cancel(Order $order, ?User $actor, ?string $reason): Order
    {
        $this->pastikanBisaDibatalkan($order);

        $reason = $reason !== null ? trim($reason) : '';
        $note = $reason !== '' ? $reason : 'Dibatalkan oleh customer.';

        $cancelled = DB::transaction(function () use ($order, $actor, $note) {
            // Kunci order lebih dulu. Aksi mitra dan webhook pembayaran (P4b) mengunci baris yang sama,
            // jadi status dan payment berubah bersama atau tidak sama sekali.
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $cancelled = $this->status->transition($order, OrderStatus::CANCELLED, $actor, $note, 'customer');

            // Sudah bayar lalu batal: catat bahwa uang customer perlu dikembalikan.
            // Admin memprosesnya manual (D-08). Payment "pending" sengaja tidak disentuh (ditangani P4b).
            $cancelled->payments()
                ->where('payment_status', 'paid')
                ->get()
                ->each(fn($p) => $p->update([
                    'payment_status' => 'refund_pending',
                    'refund_amount' => $p->amount, // tanpa biaya pembatalan di MVP
                ]));

            return $cancelled;
        });

        // Tutup tawaran mitra yang masih menggantung (kalau batal terjadi saat masih mencari mitra)
        $this->matching->advance($cancelled);

        return $cancelled->fresh();
    }

    // Customer mengonfirmasi pekerjaan selesai. Hanya dari mitra_completed (docs desain 10.4).
    public function confirmCompletion(Order $order, ?User $actor): Order
    {
        $pesan = match ($order->order_status) {
            OrderStatus::MITRA_COMPLETED => null,
            OrderStatus::COMPLETED => 'Pesanan sudah dikonfirmasi selesai.',
            OrderStatus::CANCELLED => 'Pesanan sudah dibatalkan.',
            default => 'Mitra belum menandai pekerjaan selesai.',
        };
        if ($pesan !== null) {
            throw new ApiException($pesan, 409);
        }

        return $this->status->transition(
            $order,
            OrderStatus::COMPLETED,
            $actor,
            'Customer mengonfirmasi pekerjaan selesai.',
            'customer'
        );
    }

    private function pastikanBisaDibatalkan(Order $order): void
    {
        $pesan = match ($order->order_status) {
            OrderStatus::IN_PROGRESS, OrderStatus::MITRA_COMPLETED
                => 'Pesanan sedang atau sudah selesai dikerjakan, tidak bisa dibatalkan.',
            OrderStatus::COMPLETED => 'Pesanan sudah selesai.',
            OrderStatus::CANCELLED => 'Pesanan sudah dibatalkan.',
            default => null,
        };

        if ($pesan !== null) {
            throw new ApiException($pesan, 409);
        }
    }
}