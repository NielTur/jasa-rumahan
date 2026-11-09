<?php

namespace App\Http\Requests;

class LoginRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}