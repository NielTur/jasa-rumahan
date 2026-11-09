<?php

namespace App\Http\Requests;

class RegisterMitraRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^08[0-9]{8,12}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'bio' => ['nullable', 'string'],
            'service_area' => ['nullable', 'string', 'max:255'],
        ];
    }
}