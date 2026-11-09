<?php

namespace App\Http\Requests;

class SlotRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'integer' => ':attribute harus berupa angka.',
            'date_format' => 'Format :attribute harus YYYY-MM-DD.',
            'after_or_equal' => ':attribute tidak boleh sebelum hari ini.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'service_id' => 'Jasa',
            'date' => 'Tanggal',
        ]);
    }
}