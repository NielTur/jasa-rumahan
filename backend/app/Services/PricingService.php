<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\PropertySize;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServicePrice;

// Satu-satunya tempat harga dihitung (B-3). Total dari Flutter tidak pernah dipercaya.
// Nanti dipakai juga saat membuat pesanan (P2) supaya angkanya pasti sama dengan quote.
class PricingService
{
    /**
     * @param  array<int, array{service_addon_id:int, quantity?:int}>  $addons
     * @return array<string, mixed>
     */
    public function quote(int $serviceId, int $propertySizeId, array $addons = []): array
    {
        $service = Service::where('id', $serviceId)->where('is_active', true)->first();
        if (!$service) {
            throw new ApiException('Jasa tidak ditemukan atau sedang tidak aktif.', 422);
        }

        $size = PropertySize::where('id', $propertySizeId)
            ->where('is_active', true)
            ->whereHas('propertyType', fn($q) => $q->where('is_active', true))
            ->first();
        if (!$size) {
            throw new ApiException('Ukuran properti tidak ditemukan atau sedang tidak aktif.', 422);
        }

        $price = ServicePrice::where('service_id', $service->id)
            ->where('property_size_id', $size->id)
            ->where('is_active', true)
            ->first();
        if (!$price) {
            throw new ApiException('Jasa ini belum tersedia untuk ukuran properti tersebut.', 422);
        }

        // Add-on harus milik jasa yang dipilih dan aktif
        $quantities = [];
        foreach ($addons as $item) {
            $quantities[(int) $item['service_addon_id']] = (int) ($item['quantity'] ?? 1);
        }

        $addonRows = ServiceAddon::where('service_id', $service->id)
            ->where('is_active', true)
            ->whereIn('id', array_keys($quantities))
            ->get();

        if ($addonRows->count() !== count($quantities)) {
            throw new ApiException('Ada add-on yang tidak valid untuk jasa ini.', 422);
        }

        $addonLines = [];
        $addonsTotal = 0;
        foreach ($addonRows as $addon) {
            $qty = $quantities[$addon->id];
            $subtotal = $addon->price * $qty;
            $addonsTotal += $subtotal;

            $addonLines[] = [
                'service_addon_id' => $addon->id,
                'name' => $addon->addon_name,
                'unit_price' => $addon->price,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
        }

        return [
            'service_id' => $service->id,
            'service_name' => $service->service_name,
            'property_size_id' => $size->id,
            'size_name' => $size->size_name,
            'duration_minutes' => $service->duration_minutes,
            'addons' => $addonLines,
            'pricing' => [
                'service_price' => $price->price,
                'addons_total' => $addonsTotal,
                'total_price' => $price->price + $addonsTotal,
            ],
        ];
    }
}