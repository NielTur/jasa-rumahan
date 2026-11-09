<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use App\Support\PhoneNormalizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

// Base class: error validasi dibalas 422 sesuai kontrak, pesan Bahasa Indonesia,
// dan nomor telepon dinormalisasi sebelum divalidasi (D-14).
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => PhoneNormalizer::normalize((string) $this->input('phone'))]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error('Data yang dikirim tidak valid.', 422, $validator->errors()->toArray())
        );
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'array' => ':attribute tidak valid.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'unique' => ':attribute sudah terdaftar.',
            'regex' => 'Format :attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'Nama lengkap',
            'phone' => 'Nomor telepon',
            'password' => 'Password',
            'bio' => 'Bio',
            'service_area' => 'Wilayah layanan',
            'address' => 'Alamat',
            'address.label' => 'Label alamat',
            'address.full_address' => 'Alamat lengkap',
            'address.city' => 'Kota',
            'address.province' => 'Provinsi',
            'address.postal_code' => 'Kode pos',
        ];
    }
}