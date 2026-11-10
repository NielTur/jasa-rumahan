<?php

namespace App\Support;

use App\Exceptions\ApiException;
use App\Models\MitraProfile;
use Illuminate\Http\Request;

// Ambil profil mitra dari user yang login. Dipakai semua controller /mitra/*.
// PENTING (B-11): yang dikembalikan MitraProfile, jadi ->id = mitra_id, bukan users.id.
class CurrentMitra
{
    public static function profile(Request $request): MitraProfile
    {
        $profile = MitraProfile::with('user')
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$profile) {
            throw new ApiException('Profil mitra tidak ditemukan.', 403);
        }

        return $profile;
    }
}