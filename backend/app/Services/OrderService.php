<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderAddon;
use App\Models\Service;
use App\Models\User;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Membuat pesanan (guest atau login). Harga SELALU dihitung ulang di server (B-3).
class OrderService
{
    public function __construct(
        private PricingService $pricing,
        private SchedulingService $scheduling,
        private OrderStatusService $status,
        private MatchingService $matching,
    ) {
    }

    public function create(array $data, ?User $user): Order
    {
        if ($user && $user->role !== 'customer') {
            throw new ApiException('Hanya customer atau guest yang bisa membuat pesanan.', 403);
        }

        // 1. Harga: dihitung ulang dari database, total dari Flutter tidak dipakai
        $quote = $this->pricing->quote(
            (int) $data['service_id'],
            (int) $data['property_size_id'],
            $data['addons'] ?? []
        );
        $service = Service::bookable()->findOrFail($data['service_id']);

        // 2. Jadwal: harus tepat salah satu slot, cukup jauh dari sekarang, dan ada mitra eligible
        $scheduledAt = Carbon::parse($data['scheduled_at'])->setTimezone(config('app.timezone'));
        $slot = $this->scheduling->findSlot($scheduledAt);
        if (!$slot) {
            throw new ApiException('Waktu pesanan harus salah satu slot yang tersedia.', 422);
        }
        [$start, $end] = $slot;

        if (!$this->scheduling->isFarEnough($start)) {
            throw new ApiException('Slot ini terlalu dekat dengan waktu sekarang. Pilih slot berikutnya.', 422);
        }
        if ($this->scheduling->eligibleMitraIds($service, $start, $end)->isEmpty()) {
            throw new ApiException('Slot waktu ini sudah tidak tersedia. Pilih slot lain.', 409);
        }

        // 3. Alamat tersimpan (opsional, hanya milik user yang login)
        $addressId = null;
        if (!empty($data['customer_address_id'])) {
            $address = $user
                ? CustomerAddress::where('id', $data['customer_address_id'])->where('customer_id', $user->id)->first()
                : null;
            if (!$address) {
                throw new ApiException('Alamat tersimpan tidak ditemukan.', 422);
            }
            $addressId = $address->id;
        }

        // 4. Cegah dobel-tap: kunci singkat + cek pesanan identik yang masih berjalan
        $lock = Cache::lock("create-order:{$data['customer_phone']}:{$service->id}:{$start->timestamp}", 10);
        if (!$lock->get()) {
            throw new ApiException('Pesanan sedang diproses. Mohon tunggu sebentar.', 409);
        }

        try {
            $duplicate = Order::where('customer_phone', $data['customer_phone'])
                ->where('service_id', $service->id)
                ->where('scheduled_at', $start->format('Y-m-d H:i:s'))
                ->whereNotIn('order_status', [OrderStatus::CANCELLED, OrderStatus::COMPLETED])
                ->exists();
            if ($duplicate) {
                throw new ApiException('Pesanan yang sama sudah dibuat. Cek lewat menu Lacak Pesanan.', 409);
            }

            $order = DB::transaction(function () use ($data, $user, $addressId, $quote, $start) {
                $order = Order::create([
                    'order_code' => $this->generateOrderCode(),
                    'customer_id' => $user?->id,
                    'customer_address_id' => $addressId,
                    'mitra_id' => null,
                    'service_id' => $quote['service_id'],
                    'property_size_id' => $quote['property_size_id'],
                    'scheduled_at' => $start,
                    'duration_minutes' => $quote['duration_minutes'],
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'service_address' => $data['service_address'],
                    'address_notes' => $data['address_notes'] ?? null,
                    'customer_note' => $data['customer_note'] ?? null,
                    'service_price' => $quote['pricing']['service_price'],
                    'addons_total' => $quote['pricing']['addons_total'],
                    'total_price' => $quote['pricing']['total_price'],
                    'order_status' => OrderStatus::PENDING,
                ]);

                // Snapshot add-on: nama dan harga disalin, bukan referensi
                foreach ($quote['addons'] as $line) {
                    OrderAddon::create([
                        'order_id' => $order->id,
                        'service_addon_id' => $line['service_addon_id'],
                        'addon_name' => $line['name'],
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                $this->status->logCreated($order, $user);
                $this->status->transition($order, OrderStatus::SEARCHING_MITRA, null, 'Mencari mitra.', 'system');

                return $order->fresh();
            });

            // Tawarkan ke mitra pertama (attempt #1), di luar transaksi pembuatan order
            return $this->matching->advance($order);
        } finally {
            $lock->release();
        }
    }

    // D-10: JR- + 8 karakter acak tanpa 0, O, 1, I. Ulangi kalau bentrok.
    private function generateOrderCode(): string
    {
        $cfg = config('marketplace.order_code');
        $alphabet = $cfg['alphabet'];
        $max = strlen($alphabet) - 1;

        for ($try = 0; $try < 10; $try++) {
            $code = $cfg['prefix'];
            for ($i = 0; $i < $cfg['length']; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }

            if (!Order::where('order_code', $code)->exists()) {
                return $code;
            }
        }

        throw new ApiException('Gagal membuat kode pesanan. Coba lagi.', 500);
    }
}