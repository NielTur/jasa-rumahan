<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Kedaluwarsa pembayaran (D-06, D-24) dan auto-complete (D-07).
// Dipanggil dari dua arah (B-15): scheduler lewat command, dan lazy dari endpoint baca.
// Perubahan status HANYA lewat OrderStatusService (B-4).
class OrderExpiryService
{
    public function __construct(private OrderStatusService $status)
    {
    }

    // ---------- lazy (endpoint baca) ----------

    // Murah kalau tidak ada yang jatuh tempo: hanya beberapa SELECT, tanpa kunci.
    // Kalau ada yang jatuh tempo, baris order dikunci lalu semuanya dicek ulang.
    public function refresh(Order $order): Order
    {
        if (!$this->adaYangJatuhTempo($order)) {
            return $order;
        }

        $this->proses($order, true, true);

        // Kembalikan instance baru supaya status dan relasi tidak basi
        return $order->fresh();
    }

    // Untuk endpoint daftar: dibereskan dulu sebelum difilter
    public function refreshMany(iterable $orders): void
    {
        foreach ($orders as $order) {
            $this->refresh($order);
        }
    }

    // ---------- scheduler (command) ----------

    // payments:expire-pending. Mengembalikan jumlah order yang berubah.
    public function expirePendingPayments(): int
    {
        $idBayarLewat = Payment::where('payment_status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->pluck('order_id');

        $idMenunggu = Order::where('order_status', OrderStatus::WAITING_PAYMENT)->pluck('id');

        return $this->prosesBanyak($idBayarLewat->merge($idMenunggu)->unique(), true, false);
    }

    // orders:auto-complete. Mengembalikan jumlah order yang berubah.
    public function autoCompleteDue(): int
    {
        $ids = Order::where('order_status', OrderStatus::MITRA_COMPLETED)->pluck('id');

        return $this->prosesBanyak($ids, false, true);
    }

    // ---------- inti ----------

    private function prosesBanyak(Collection $ids, bool $bayar, bool $selesai): int
    {
        $jumlah = 0;

        foreach ($ids as $id) {
            $order = Order::find($id);
            if ($order && $this->proses($order, $bayar, $selesai)) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    // Kunci order, cek ulang kondisinya, lalu terapkan. Aman dipanggil berulang (idempotent)
    // dan bersamaan dengan webhook pembayaran, karena webhook juga mengunci baris order yang sama.
    private function proses(Order $order, bool $bayar, bool $selesai): bool
    {
        return DB::transaction(function () use ($order, $bayar, $selesai) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $berubah = false;

            if ($bayar) {
                $berubah = $this->prosesPembayaran($locked) || $berubah;
            }
            if ($selesai) {
                $berubah = $this->prosesSelesaiOtomatis($locked) || $berubah;
            }

            return $berubah;
        });
    }

    private function prosesPembayaran(Order $locked): bool
    {
        // 1. Payment pending yang lewat expired_at jadi expired, apa pun status ordernya.
        //    (Cancel customer membiarkan payment pending, jadi ia kedaluwarsa sendiri: D-22)
        $jumlah = Payment::where('order_id', $locked->id)
            ->where('payment_status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['payment_status' => 'expired']);
        $berubah = $jumlah > 0;

        // 2. Order yang menunggu bayar dibatalkan sistem kalau batas bayar habis
        if ($this->batasBayarLewat($locked)) {
            $this->status->transition(
                $locked,
                OrderStatus::CANCELLED,
                null,
                'Batas waktu pembayaran habis.',
                'system'
            );
            $berubah = true;
        }

        return $berubah;
    }

    private function prosesSelesaiOtomatis(Order $locked): bool
    {
        if (!$this->selesaiOtomatisJatuhTempo($locked)) {
            return false;
        }

        $jam = (int) config('marketplace.auto_complete_hours');
        $this->status->transition(
            $locked,
            OrderStatus::COMPLETED,
            null,
            "Otomatis selesai: customer tidak mengonfirmasi dalam {$jam} jam.",
            'system'
        );

        return true;
    }

    // ---------- pengecekan (tanpa mengubah apa pun) ----------

    private function adaYangJatuhTempo(Order $order): bool
    {
        return $this->adaPaymentLewat($order)
            || $this->batasBayarLewat($order)
            || $this->selesaiOtomatisJatuhTempo($order);
    }

    private function adaPaymentLewat(Order $order): bool
    {
        return $order->payments()
            ->where('payment_status', 'pending')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->exists();
    }

    // D-06 + D-24: batas bayar dihitung dari transisi waiting_payment, bukan dari payments.expired_at
    private function batasBayarLewat(Order $order): bool
    {
        if ($order->order_status !== OrderStatus::WAITING_PAYMENT) {
            return false;
        }

        // Yang sudah dibayar tidak boleh dibatalkan oleh expiry
        if ($order->payments()->where('payment_status', 'paid')->exists()) {
            return false;
        }

        // Percobaan bayar terakhir sudah expired dan tidak ada yang pending: tidak ada harapan lagi
        $terakhir = $order->payments()->orderByDesc('id')->first();
        if ($terakhir && $terakhir->payment_status === 'expired') {
            return true;
        }

        $batas = $this->sejak($order, OrderStatus::WAITING_PAYMENT)
            ->addMinutes((int) config('marketplace.payment_expiry_minutes'));

        return $batas->lte(now());
    }

    // D-07
    private function selesaiOtomatisJatuhTempo(Order $order): bool
    {
        if ($order->order_status !== OrderStatus::MITRA_COMPLETED) {
            return false;
        }

        $batas = $this->sejak($order, OrderStatus::MITRA_COMPLETED)
            ->addHours((int) config('marketplace.auto_complete_hours'));

        return $batas->lte(now());
    }

    // Kapan order masuk ke status ini (dari riwayat). Tanpa riwayat: pakai updated_at.
    private function sejak(Order $order, string $status): Carbon
    {
        $baris = OrderStatusHistory::where('order_id', $order->id)
            ->where('new_status', $status)
            ->orderByDesc('id')
            ->first();

        return ($baris?->created_at ?? $order->updated_at)->copy();
    }
}