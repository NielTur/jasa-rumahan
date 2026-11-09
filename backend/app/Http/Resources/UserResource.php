<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Nama field jelas supaya ID tidak tertukar (B-11):
// user_id = users.id, mitra_id = mitra_profiles.id
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'user_id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'account_status' => $this->account_status,
        ];

        if ($this->role === 'mitra' && $this->mitraProfile) {
            $data['mitra_id'] = $this->mitraProfile->id;
            $data['verification_status'] = $this->mitraProfile->verification_status;
        }

        return $data;
    }
}