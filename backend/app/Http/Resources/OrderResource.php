<?php

namespace App\Http\Resources;

use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Bentuk objek Order sesuai docs/API_CONTRACT.md 4
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['service', 'propertySize', 'addons', 'mitra.user', 'payments']);

        $payment = $this->payments->sortByDesc('id')->first();

        return [
            'order_code' => $this->order_code,
            'order_status' => $this->order_status,
            'tracking_stage' => OrderStatus::trackingStage($this->order_status),
            'service' => [
                'id' => $this->service_id,
                'service_name' => $this->service->service_name,
            ],
            'property_size' => [
                'id' => $this->property_size_id,
                'size_name' => $this->propertySize->size_name,
            ],
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
            ],
            'service_address' => $this->service_address,
            'address_notes' => $this->address_notes,
            'customer_note' => $this->customer_note,
            'pricing' => [
                'service_price' => $this->service_price,
                'addons_total' => $this->addons_total,
                'total_price' => $this->total_price,
            ],
            'addons' => $this->addons->map(fn($a) => [
                'name' => $a->addon_name,
                'unit_price' => $a->unit_price,
                'quantity' => $a->quantity,
                'subtotal' => $a->subtotal,
            ])->values()->all(),
            // Telepon mitra tampil setelah mitra ditetapkan (kontrak 4)
            'mitra' => $this->mitra ? [
                'mitra_id' => $this->mitra->id,
                'full_name' => $this->mitra->user->full_name,
                'phone' => $this->mitra->user->phone,
            ] : null,
            'payment' => $payment ? [
                'payment_status' => $payment->payment_status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'expired_at' => $payment->expired_at?->toIso8601String(),
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}