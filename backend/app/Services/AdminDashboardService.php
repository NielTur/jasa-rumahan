<?php

namespace App\Services;

use App\Models\MitraProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Support\OrderStatus;

// Angka ringkasan untuk dashboard admin. Controller tipis, semua hitungan di sini (B-1).
class AdminDashboardService
{
    public function summary(): array
    {
        $perStatus = Order::selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        // Semua status ditampilkan berurutan sesuai alur, termasuk yang jumlahnya 0
        $statusCounts = [];
        foreach (array_keys(OrderStatus::TRANSITIONS) as $status) {
            $statusCounts[] = [
                'status' => $status,
                'label' => OrderStatus::label($status),
                'total' => (int) $perStatus->get($status, 0),
            ];
        }

        // "Sedang berjalan" = belum selesai dan belum batal
        $aktif = array_merge([OrderStatus::PENDING, OrderStatus::SEARCHING_MITRA], OrderStatus::SCHEDULE_BLOCKING);
        $aktifTotal = collect($aktif)->sum(fn($s) => (int) $perStatus->get($s, 0));

        $awalHari = now()->startOfDay();
        $akhirHari = now()->endOfDay();

        return [
            'total_orders' => (int) $perStatus->sum(),
            'active_orders' => (int) $aktifTotal,
            'created_today' => Order::whereBetween('created_at', [$awalHari, $akhirHari])->count(),
            'scheduled_today' => Order::whereBetween('scheduled_at', [$awalHari, $akhirHari])
                ->where('order_status', '!=', OrderStatus::CANCELLED)
                ->count(),
            'status_counts' => $statusCounts,
            // Uang yang harus dikembalikan manual oleh admin (D-08)
            'refund_pending_count' => Payment::where('payment_status', 'refund_pending')->count(),
            'refund_pending_amount' => (int) Payment::where('payment_status', 'refund_pending')->sum('refund_amount'),
            'mitra_pending' => MitraProfile::where('verification_status', 'pending')->count(),
        ];
    }
}