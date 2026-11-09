<?php

namespace App\Support;

// D-14: telepon disimpan format lokal 08xxxx. +62 / 62 diubah jadi 0.
class PhoneNormalizer
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // buang spasi, strip, tanda +, dan karakter non-angka lain
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }
}