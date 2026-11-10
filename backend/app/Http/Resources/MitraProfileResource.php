<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Resource ini membungkus MitraProfile. user_id dan mitra_id sengaja dua key terpisah (B-11).
class MitraProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('user');

        return [
            'user_id' => $this->user_id,
            'mitra_id' => $this->id,
            'full_name' => $this->user->full_name,
            'phone' => $this->user->phone,
            'bio' => $this->bio,
            'service_area' => $this->service_area,
            'verification_status' => $this->verification_status,
            'is_available' => (bool) $this->is_available,
        ];
    }
}