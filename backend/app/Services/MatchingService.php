<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderMitraAttempt;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

// Pencarian mitra bertahap (docs desain 9.3): satu mitra per attempt, dicatat di order_mitra_attempts.
// Batas waktu dan batas percobaan dari config/marketplace.php (B-6), bukan angka literal.
class MatchingService
{
    public function __construct(
        private SchedulingService $scheduling,
        private OrderStatusService $status,
    ) {
    }

    // Dipakai endpoint baca (lazy, B-15). Murah kalau order sudah tidak mencari mitra.
    public function refreshIfSearching(Order $order): Order
    {
        if ($order->order_status !== OrderStatus::SEARCHING_MITRA) {
            return $order;
        }

        return $this->advance($order);
    }

    /**
     * Majukan pencarian: tutup offer kedaluwarsa, lalu tawarkan ke mitra berikutnya
     * atau batalkan order. Aman dipanggil berulang (idempotent) dan dari banyak proses
     * sekaligus, karena baris order dikunci.
     */
    public function advance(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Order sudah tidak mencari mitra: tutup offer yang masih menggantung
            if ($locked->order_status !== OrderStatus::SEARCHING_MITRA) {
                OrderMitraAttempt::where('order_id', $locked->id)
                    ->where('status', 'offered')
                    ->update(['status' => 'expired', 'responded_at' => now()]);

                return $locked;
            }

            // 1. Offer yang lewat batas waktu jadi expired
            OrderMitraAttempt::where('order_id', $locked->id)
                ->where('status', 'offered')
                ->where('offered_at', '<=', $this->deadline())
                ->update(['status' => 'expired', 'responded_at' => now()]);

            // 2. Masih ada offer aktif: tunggu respons mitra itu
            $hasActive = OrderMitraAttempt::where('order_id', $locked->id)
                ->where('status', 'offered')
                ->exists();
            if ($hasActive) {
                return $locked;
            }

            // 3. Batas percobaan habis: batalkan (D-05)
            $attempts = OrderMitraAttempt::where('order_id', $locked->id)->count();
            if ($attempts >= (int) config('marketplace.max_mitra_attempts')) {
                return $this->cancelNoMitra($locked);
            }

            // 4. Cari mitra berikutnya
            $mitraId = $this->nextCandidate($locked);
            if ($mitraId === null) {
                return $this->cancelNoMitra($locked);
            }

            OrderMitraAttempt::create([
                'order_id' => $locked->id,
                'mitra_id' => $mitraId, // mitra_profiles.id (B-11)
                'attempt_number' => $attempts + 1,
                'status' => 'offered',
                'offered_at' => now(),
                'responded_at' => null,
            ]);

            return $locked;
        });
    }

    // Untuk scheduler: proses semua order yang punya offer kedaluwarsa. Mengembalikan jumlah order.
    public function processAllExpired(): int
    {
        $ids = OrderMitraAttempt::where('status', 'offered')
            ->where('offered_at', '<=', $this->deadline())
            ->pluck('order_id')
            ->unique();

        foreach ($ids as $id) {
            $order = Order::find($id);
            if ($order) {
                $this->advance($order);
            }
        }

        return $ids->count();
    }

    private function deadline()
    {
        return now()->subSeconds((int) config('marketplace.mitra_response_seconds'));
    }

    private function cancelNoMitra(Order $order): Order
    {
        return $this->status->transition(
            $order,
            OrderStatus::CANCELLED,
            null,
            'Tidak ada mitra yang menerima pesanan.',
            'system'
        );
    }

    // D-16: mitra eligible yang belum pernah ditawari. Order aktif paling sedikit di hari itu
    // lebih dulu, sisanya acak. service_area tidak dipakai di MVP.
    private function nextCandidate(Order $order): ?int
    {
        $slot = $this->scheduling->findSlot($order->scheduled_at);
        [$start, $end] = $slot ?? [$order->scheduled_at->copy(), $order->scheduled_at->copy()->addHours(2)];

        $tried = OrderMitraAttempt::where('order_id', $order->id)->pluck('mitra_id');

        $candidates = $this->scheduling
            ->eligibleMitraIds($order->service, $start, $end)
            ->diff($tried)
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $load = Order::whereIn('mitra_id', $candidates)
            ->whereDate('scheduled_at', $start->toDateString())
            ->whereIn('order_status', OrderStatus::SCHEDULE_BLOCKING)
            ->selectRaw('mitra_id, COUNT(*) as total')
            ->groupBy('mitra_id')
            ->pluck('total', 'mitra_id');

        return $candidates
            ->shuffle()
            ->sortBy(fn($id) => $load->get($id, 0))
            ->values()
            ->first();
    }
}