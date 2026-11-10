<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\MitraProfile;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Pagination\LengthAwarePaginator;

// Sisi mitra untuk pesanan yang sudah ditugaskan: daftar, detail, dan progres pengerjaan.
// Perubahan status HANYA lewat OrderStatusService (B-4).
class MitraOrderService
{
    // aksi di URL => [status tujuan, catatan di riwayat status]
    private const ACTIONS = [
        'depart' => [OrderStatus::ON_THE_WAY, 'Mitra berangkat menuju lokasi.'],
        'arrive' => [OrderStatus::ARRIVED, 'Mitra tiba di lokasi.'],
        'start' => [OrderStatus::IN_PROGRESS, 'Mitra mulai mengerjakan.'],
        'finish' => [OrderStatus::MITRA_COMPLETED, 'Mitra menandai pekerjaan selesai.'],
    ];

    public function __construct(private OrderStatusService $status)
    {
    }

    // Daftar pesanan milik mitra ini. $status boleh satu atau beberapa dipisah koma: "confirmed,on_the_way"
    public function paginate(MitraProfile $mitra, ?string $status, int $perPage): LengthAwarePaginator
    {
        $query = Order::with(['service', 'propertySize', 'addons', 'mitra.user', 'payments'])
            ->where('mitra_id', $mitra->id); // mitra_profiles.id (B-11)

        $filter = array_values(array_filter(array_map('trim', explode(',', (string) $status))));

        if ($filter) {
            $dikenal = array_keys(OrderStatus::TRANSITIONS);
            foreach ($filter as $s) {
                if (!in_array($s, $dikenal, true)) {
                    throw new ApiException("Status '{$s}' tidak dikenal.", 422);
                }
            }
            $query->whereIn('order_status', $filter);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    // Pesanan milik mitra ini. Punya mitra lain atau tidak ada = 404 (tidak bocorkan eksistensi).
    public function find(MitraProfile $mitra, string $orderCode): Order
    {
        $order = Order::with(['service', 'propertySize', 'addons', 'mitra.user', 'payments'])
            ->where('order_code', strtoupper(trim($orderCode)))
            ->where('mitra_id', $mitra->id)
            ->first();

        if (!$order) {
            throw new ApiException('Pesanan tidak ditemukan.', 404);
        }

        return $order;
    }

    /**
     * Majukan progres: depart | arrive | start | finish.
     * Urutan dan pelaku divalidasi OrderStatusService (loncat langkah = 409).
     */
    public function progress(MitraProfile $mitra, string $orderCode, string $action): Order
    {
        [$to, $note] = self::ACTIONS[$action];
        $order = $this->find($mitra, $orderCode);

        if ($action === 'depart') {
            $this->pastikanSudahBayar($order);
        }

        return $this->status->transition($order, $to, $mitra->user, $note, 'mitra');
    }

    // Mitra baru boleh berangkat kalau pembayaran terverifikasi paid (kontrak 6.1)
    private function pastikanSudahBayar(Order $order): void
    {
        if ($order->order_status === OrderStatus::WAITING_PAYMENT) {
            throw new ApiException('Menunggu pembayaran customer. Belum bisa berangkat.', 409);
        }

        if (
            $order->order_status === OrderStatus::CONFIRMED
            && !$order->payments()->where('payment_status', 'paid')->exists()
        ) {
            throw new ApiException('Pembayaran customer belum terverifikasi. Belum bisa berangkat.', 409);
        }
        // Status lain (mis. cancelled) ditolak oleh transition() dengan pesan yang sesuai
    }
}