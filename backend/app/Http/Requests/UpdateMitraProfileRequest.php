<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Mitra hanya boleh mengubah bio, wilayah, dan ketersediaan.
// verification_status sengaja tidak ada di sini (hanya admin/seeder).
class UpdateMitraProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi role sudah di middleware route
    }

    public function rules(): array
    {
        return [
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'service_area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bio.max' => 'Bio maksimal 1000 karakter.',
            'service_area.max' => 'Wilayah layanan maksimal 255 karakter.',
            'is_available.boolean' => 'Ketersediaan harus berupa true atau false.',
        ];
    }
}