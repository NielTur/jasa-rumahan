<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Dipakai untuk daftar jasa (ringkas) dan detail jasa (+ addons + prices).
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'addons' => $this->whenLoaded('addons', fn() => $this->addons->map(fn($a) => [
                'id' => $a->id,
                'addon_name' => $a->addon_name,
                'description' => $a->description,
                'price' => $a->price,
            ])->values()->all()),
            'prices' => $this->whenLoaded('prices', fn() => $this->groupedPrices()),
        ];
    }

    // Harga dikelompokkan per jenis properti (kontrak 3)
    private function groupedPrices(): array
    {
        return $this->prices
            ->groupBy(fn($p) => $p->propertySize->property_type_id)
            ->map(function ($items) {
                $type = $items->first()->propertySize->propertyType;

                return [
                    'property_type_id' => $type->id,
                    'type_name' => $type->type_name,
                    'sizes' => $items->map(fn($p) => [
                        'property_size_id' => $p->property_size_id,
                        'size_name' => $p->propertySize->size_name,
                        'min_area' => $p->propertySize->min_area,
                        'max_area' => $p->propertySize->max_area,
                        'price' => $p->price,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}