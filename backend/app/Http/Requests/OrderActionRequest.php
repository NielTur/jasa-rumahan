<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Dipakai aksi customer/guest pada satu pesanan: cancel, confirm-completion, dan cek status bayar.
// phone dikirim guest (body untuk POST, query untuk GET). Customer login tidak perlu mengirimnya.
// reason hanya dipakai saat cancel.
class OrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dicek di OrderAccessService (pemilik atau kode + telepon)
    }

    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.string' => 'Nomor telepon tidak valid.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'reason.string' => 'Alasan pembatalan tidak valid.',
            'reason.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ];
    }
}