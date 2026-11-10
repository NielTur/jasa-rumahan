<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MitraAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => (int) $this->day_of_week, // 0 = Minggu
            'start_time' => substr($this->start_time, 0, 5), // "08:00:00" -> "08:00"
            'end_time' => substr($this->end_time, 0, 5),
            'is_active' => (bool) $this->is_active,
        ];
    }
}