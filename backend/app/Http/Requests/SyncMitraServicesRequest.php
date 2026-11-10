<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Body: { "service_ids": [1, 2] }. Array kosong = mitra melepas semua jasa.
class SyncMitraServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_ids' => ['present', 'array'],
            // Hanya jasa katalog yang aktif yang boleh dipilih
            'service_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_ids.present' => 'Daftar jasa wajib dikirim (boleh kosong).',
            'service_ids.array' => 'Daftar jasa harus berupa array.',
            'service_ids.*.integer' => 'ID jasa harus berupa angka.',
            'service_ids.*.distinct' => 'ID jasa tidak boleh dobel.',
            'service_ids.*.exists' => 'Jasa tidak ditemukan atau sudah nonaktif.',
        ];
    }
}