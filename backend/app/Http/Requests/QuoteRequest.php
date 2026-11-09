<?php

namespace App\Http\Requests;

class QuoteRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer'],
            'property_size_id' => ['required', 'integer'],
            'addons' => ['nullable', 'array'],
            'addons.*.service_addon_id' => ['required', 'integer', 'distinct'],
            'addons.*.quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'integer' => ':attribute harus berupa angka.',
            'distinct' => ':attribute tidak boleh dobel.',
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
        ]);
    }
}