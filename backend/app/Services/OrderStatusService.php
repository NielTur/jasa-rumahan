<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

// SATU-SATUNYA pintu untuk mengubah status pesanan (B-4).
// Memvalidasi transisi, mengunci baris order, dan menulis order_status_histories.
class OrderStatusService
{
    // Catatan awal: pesanan baru dibuat (status null -> pending)
    public function logCreated(Order $order, ?User $actor = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'changed_by_user_id' => $actor?->id,
            'old_status' => null,
            'new_status' => $order->order_status,
            'note' => 'Pesanan dibuat.',
        ]);
    }

    /**
     * @param  string|null  $actorType  customer|mitra|admin|system. Kalau kosong, diturunkan dari $actor
     *                                  (guest yang membatalkan harus mengirim 'customer').
     */
    public function transition(Order $order, string $to, ?User $actor = null, ?string $note = null, ?string $actorType = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $note, $actorType) {
            // Kunci baris order supaya dua request bersamaan tidak saling menimpa
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->order_status;

            $allowed = OrderStatus::TRANSITIONS[$from] ?? [];
            if (!in_array($to, $allowed, true)) {
                throw new ApiException("Status pesanan tidak bisa diubah dari {$from} ke {$to}.", 409);
            }

            $locked->order_status = $to;

            if ($to === OrderStatus::CANCELLED) {
                $locked->cancelled_at = now();
                $locked->cancel_reason = $note;
                $locked->cancelled_by = $actorType ?? ($actor?->role ?? 'system');
            }

            if ($to === OrderStatus::COMPLETED) {
                $locked->completed_at = now();
            }

            $locked->save();

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'changed_by_user_id' => $actor?->id, // NULL = sistem atau guest
                'old_status' => $from,
                'new_status' => $to,
                'note' => $note,
            ]);

            return $locked;
        });
    }
}