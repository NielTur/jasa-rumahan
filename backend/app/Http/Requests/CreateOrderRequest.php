<?php

namespace App\Http\Requests;

use App\Support\PhoneNormalizer;

class CreateOrderRequest extends ApiFormRequest
{
    // Selain 'phone', telepon customer di pesanan juga dinormalisasi (D-14)
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('customer_phone')) {
            $this->merge(['customer_phone' => PhoneNormalizer::normalize((string) $this->input('customer_phone'))]);
        }
    }

    public function rules(): array
    {
        // Total harga TIDAK ada di sini: dihitung server (B-3)
        return [
            'service_id' => ['required', 'integer'],
            'property_size_id' => ['required', 'integer'],
            'addons' => ['nullable', 'array'],
            'addons.*.service_addon_id' => ['required', 'integer', 'distinct'],
            'addons.*.quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'scheduled_at' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'regex:/^08[0-9]{8,12}$/'],
            'service_address' => ['required', 'string'],
            'address_notes' => ['nullable', 'string'],
            'customer_note' => ['nullable', 'string'],
            'customer_address_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'integer' => ':attribute harus berupa angka.',
            'distinct' => ':attribute tidak boleh dobel.',
            'date' => 'Format :attribute tidak valid.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'service_id' => 'Jasa',
            'property_size_id' => 'Ukuran properti',
            'addons' => 'Add-on',
            'addons.*.service_addon_id' => 'Add-on',
            'addons.*.quantity' => 'Jumlah add-on',
            'scheduled_at' => 'Jadwal',
            'customer_name' => 'Nama',
            'customer_phone' => 'Nomor telepon',
            'service_address' => 'Alamat pengerjaan',
            'address_notes' => 'Catatan alamat',
            'customer_note' => 'Catatan',
            'customer_address_id' => 'Alamat tersimpan',
        ]);
    }
}