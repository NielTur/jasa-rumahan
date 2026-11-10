<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\MitraProfile;
use App\Models\Order;
use App\Models\OrderMitraAttempt;
use App\Support\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Sisi mitra untuk penawaran: lihat, terima (B-5), tolak.
// Urutan kunci SELALU: order dulu, baru mitra_profile (supaya tidak deadlock).
class MitraOfferService
{
    public function __construct(
        private SchedulingService $scheduling,
        private OrderStatusService $status,
        private MatchingService $matching,
    ) {
    }

    // Tawaran yang masih berlaku untuk mitra ini.
    public function activeOffers(MitraProfile $mitra): Collection
    {
        $deadline = $this->deadline();

        // Lazy expiry (B-15): tawaran mitra ini yang sudah lewat batas waktu ditutup,
        // lalu pencarian dimajukan ke mitra berikutnya. Jadi demo benar tanpa scheduler.
        OrderMitraAttempt::where('mitra_id', $mitra->id)
            ->where('status', 'offered')
            ->where('offered_at', '<=', $deadline)
            ->pluck('order_id')
            ->unique()
            ->each(function ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $this->matching->advance($order);
                }
            });

        return OrderMitraAttempt::with(['order.service', 'order.propertySize', 'order.addons'])
            ->where('mitra_id', $mitra->id)
            ->where('status', 'offered')
            ->where('offered_at', '>', $deadline)
            ->whereHas('order', fn($q) => $q->where('order_status', OrderStatus::SEARCHING_MITRA))
            ->orderBy('offered_at')
            ->get();
    }

    /**
     * B-5: terima tawaran. Semua pengecekan diulang SETELAH order dan mitra dikunci,
     * jadi request yang datang belakangan melihat hasil request yang menang (lalu dapat 409).
     */
    public function accept(MitraProfile $mitra, int $attemptId): Order
    {
        $preview = $this->ownAttempt($mitra, $attemptId); // 404 kalau bukan milik mitra ini

        return DB::transaction(function () use ($mitra, $preview) {
            // Kunci: order -> mitra_profile -> attempt
            $order = Order::whereKey($preview->order_id)->lockForUpdate()->firstOrFail();
            $profile = MitraProfile::with('user')->whereKey($mitra->id)->lockForUpdate()->firstOrFail();
            $attempt = OrderMitraAttempt::whereKey($preview->id)->lockForUpdate()->firstOrFail();

            // Mitra belum disetujui atau akun tidak aktif tidak boleh menerima order
            if ($profile->verification_status !== 'approved' || $profile->user->account_status !== 'active') {
                throw new ApiException('Akun mitra Anda belum aktif untuk menerima pesanan.', 403);
            }

            // Cek ulang kondisi order (B-5)
            if ($order->order_status !== OrderStatus::SEARCHING_MITRA) {
                throw new ApiException('Pesanan ini sudah tidak mencari mitra.', 409);
            }
            if ($order->mitra_id !== null) {
                throw new ApiException('Pesanan sudah diambil mitra lain.', 409);
            }

            // Cek ulang tawaran: masih offered dan belum kedaluwarsa
            if ($attempt->status !== 'offered') {
                throw new ApiException('Tawaran ini sudah tidak berlaku.', 409);
            }
            if ($attempt->offered_at->lte($this->deadline())) {
                throw new ApiException('Tawaran sudah kedaluwarsa.', 409);
            }

            // Cek ulang jadwal: tidak bentrok dengan order lain milik mitra ini.
            // Karena mitra_profile dikunci, dua order berjam sama yang di-accept bersamaan
            // diproses satu per satu, jadi yang kedua melihat yang pertama dan ditolak.
            [$start, $end] = $this->scheduling->findSlot($order->scheduled_at)
                ?? [$order->scheduled_at->copy(), $order->scheduled_at->copy()->addHours(2)];

            if (!$this->scheduling->eligibleMitraIds($order->service, $start, $end)->contains($profile->id)) {
                throw new ApiException('Jadwal Anda bentrok atau Anda tidak lagi memenuhi syarat untuk pesanan ini.', 409);
            }

            // Semua lolos: tetapkan mitra (mitra_id = mitra_profiles.id, B-11)
            $attempt->status = 'accepted';
            $attempt->responded_at = now();
            $attempt->save();

            $order->mitra_id = $profile->id;
            $order->save();

            // Status hanya berubah lewat OrderStatusService (B-4)
            return $this->status->transition(
                $order,
                OrderStatus::WAITING_PAYMENT,
                $profile->user,
                'Mitra menerima pesanan.',
                'mitra'
            );
        });
    }

    // Tolak tawaran, lalu pencarian dimajukan ke mitra berikutnya (atau order dibatalkan).
    public function reject(MitraProfile $mitra, int $attemptId): void
    {
        $preview = $this->ownAttempt($mitra, $attemptId);

        DB::transaction(function () use ($preview) {
            $order = Order::whereKey($preview->order_id)->lockForUpdate()->firstOrFail();
            $attempt = OrderMitraAttempt::whereKey($preview->id)->lockForUpdate()->firstOrFail();

            if ($order->order_status !== OrderStatus::SEARCHING_MITRA || $attempt->status !== 'offered') {
                throw new ApiException('Tawaran ini sudah tidak berlaku.', 409);
            }

            $attempt->status = 'rejected';
            $attempt->responded_at = now();
            $attempt->save();
        });

        // Di luar transaksi: advance() mengunci order sendiri
        $this->matching->advance(Order::findOrFail($preview->order_id));
    }

    // Tawaran milik mitra ini. Punya mitra lain atau tidak ada = 404 (tidak bocorkan eksistensi).
    private function ownAttempt(MitraProfile $mitra, int $attemptId): OrderMitraAttempt
    {
        $attempt = OrderMitraAttempt::whereKey($attemptId)->where('mitra_id', $mitra->id)->first();

        if (!$attempt) {
            throw new ApiException('Tawaran tidak ditemukan.', 404);
        }

        return $attempt;
    }

    // Sama dengan MatchingService::deadline() (B-6: dari config, bukan angka literal)
    private function deadline()
    {
        return now()->subSeconds((int) config('marketplace.mitra_response_seconds'));
    }
}