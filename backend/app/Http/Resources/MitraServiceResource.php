<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Satu jasa katalog + flag selected. `selected` diisi MitraSetupService::catalogWithSelection().
class MitraServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'selected' => (bool) $this->selected,
        ];
    }
}