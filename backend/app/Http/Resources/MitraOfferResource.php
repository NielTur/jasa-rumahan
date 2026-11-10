<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Membungkus OrderMitraAttempt. D-12: TANPA nama/telepon/alamat customer dan tanpa order_code.
// Detail lengkap baru ada di respons accept dan /mitra/orders/{kode}.
class MitraOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->order;
        $expiresAt = $this->offered_at->copy()
            ->addSeconds((int) config('marketplace.mitra_response_seconds'));

        return [
            'attempt_id' => $this->id,
            'attempt_number' => $this->attempt_number,
            'offered_at' => $this->offered_at->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(), // untuk countdown di Flutter
            'service' => [
                'id' => $order->service_id,
                'service_name' => $order->service->service_name,
            ],
            'property_size' => [
                'id' => $order->property_size_id,
                'size_name' => $order->propertySize->size_name,
            ],
            'scheduled_at' => $order->scheduled_at->toIso8601String(),
            'duration_minutes' => $order->duration_minutes,
            'addons' => $order->addons->map(fn($a) => [
                'name' => $a->addon_name,
                'quantity' => $a->quantity,
            ])->values()->all(),
            'pricing' => [
                'service_price' => $order->service_price,
                'addons_total' => $order->addons_total,
                'total_price' => $order->total_price,
            ],
        ];
    }
}