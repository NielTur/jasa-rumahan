<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\MitraProfile;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

// Pengelolaan akun dan mitra oleh admin. Controller tipis, logika di sini (B-1).
class AdminAccountService
{
    public function users(array $filters, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['role'] ?? null, fn($q, $v) => $q->where('role', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('account_status', $v))
            ->when($filters['q'] ?? null, fn($q, $v) => $q->where(
                fn($w) => $w->where('full_name', 'like', "%{$v}%")->orWhere('phone', 'like', "%{$v}%")
            ))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function mitra(?string $status, ?string $q, int $perPage): LengthAwarePaginator
    {
        return MitraProfile::with('user')
            ->withCount([
                'services as active_services_count' => fn($s) => $s->where('mitra_services.status', 'active'),
                'orders',
            ])
            ->when($status, fn($x, $v) => $x->where('verification_status', $v))
            ->when($q, fn($x, $v) => $x->whereHas('user', fn($u) => $u->where(
                fn($w) => $w->where('full_name', 'like', "%{$v}%")->orWhere('phone', 'like', "%{$v}%")
            )))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Ubah status akun: active atau suspended.
     * - Akun admin tidak boleh diubah dari dashboard (403).
     * - Selain active, semua token API dicabut: token lama tidak boleh tetap hidup setelah disuspend.
     */
    public function setAccountStatus(int $userId, string $status): User
    {
        return DB::transaction(function () use ($userId, $status) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();

            if ($user->role === 'admin') {
                throw new ApiException('Akun admin tidak bisa diubah dari dashboard.', 403);
            }

            $user->update(['account_status' => $status]);

            if ($status !== 'active') {
                $user->tokens()->delete();
            }

            return $user;
        });
    }

    // Setujui atau tolak mitra. Hanya mitra approved yang ditawari pesanan dan boleh accept.
    public function setVerification(int $mitraId, string $status): MitraProfile
    {
        $mitra = MitraProfile::with('user')->findOrFail($mitraId);
        $mitra->update(['verification_status' => $status]);

        return $mitra;
    }
}