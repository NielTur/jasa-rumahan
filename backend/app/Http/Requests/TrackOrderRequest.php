<?php

namespace App\Http\Requests;

class TrackOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'order_code' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'order_code' => 'Kode pesanan',
        ]);
    }
}