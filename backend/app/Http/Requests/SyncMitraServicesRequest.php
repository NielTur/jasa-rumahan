<?php

namespace App\Http\Requests;

use App\Models\Service;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

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
            'service_ids.*' => [
                'bail',
                'integer',
                'distinct',
                // CR-16: hanya jasa yang boleh dipesan (jasa aktif DAN kategorinya aktif)
                function (string $attribute, mixed $value, Closure $fail) {
                    if (!Service::bookable()->whereKey($value)->exists()) {
                        $fail('Jasa tidak ditemukan atau sudah nonaktif.');
                    }
                },
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
        ];
    }
}