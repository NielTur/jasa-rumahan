<?php

namespace App\Http\Requests;

class RegisterCustomerRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^08[0-9]{8,12}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'array'],
            'address.label' => ['required', 'string', 'max:50'],
            'address.full_address' => ['required', 'string'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.province' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['nullable', 'string', 'max:10'],
        ];
    }
}