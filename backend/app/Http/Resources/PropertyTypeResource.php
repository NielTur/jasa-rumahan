<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_name' => $this->type_name,
            'sizes' => $this->whenLoaded('sizes', fn() => $this->sizes->map(fn($s) => [
                'id' => $s->id,
                'size_name' => $s->size_name,
                'min_area' => $s->min_area,
                'max_area' => $s->max_area,
            ])->values()->all()),
        ];
    }
}