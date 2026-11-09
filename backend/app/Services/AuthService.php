<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CustomerAddress;
use App\Models\MitraProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Logika register/login. Controller hanya memanggil class ini (B-1).
class AuthService
{
    /** @return array{0: User, 1: string} [user, token] */
    public function registerCustomer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => 'customer',
                'account_status' => 'active',
            ]);

            // Alamat saat register dijadikan alamat utama
            $alamat = $data['address'];
            CustomerAddress::create([
                'customer_id' => $user->id,
                'address_label' => $alamat['label'],
                'recipient_name' => $user->full_name,
                'recipient_phone' => $user->phone,
                'full_address' => $alamat['full_address'],
                'city' => $alamat['city'] ?? null,
                'province' => $alamat['province'] ?? null,
                'postal_code' => $alamat['postal_code'] ?? null,
                'is_primary' => true,
            ]);

            return [$user, $this->createToken($user)];
        });
    }

    /** @return array{0: User, 1: string} [user, token] */
    public function registerMitra(array $data): array
    {
        // users + mitra_profiles harus tersimpan bersamaan, jadi pakai transaksi
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => 'mitra',
                'account_status' => 'active',
            ]);

            // D-15: MITRA_AUTO_APPROVE=true -> langsung approved (dev/demo)
            MitraProfile::create([
                'user_id' => $user->id,
                'bio' => $data['bio'] ?? null,
                'service_area' => $data['service_area'] ?? null,
                'verification_status' => config('marketplace.mitra_auto_approve') ? 'approved' : 'pending',
                'is_available' => true,
            ]);

            return [$user, $this->createToken($user)];
        });
    }

    /** @return array{0: User, 1: string} [user, token] */
    public function login(string $phone, string $password): array
    {
        $user = User::where('phone', $phone)->first();

        // Pesan sengaja sama untuk "nomor tidak ada" dan "password salah"
        if (!$user || !Hash::check($password, $user->password)) {
            throw new ApiException('Nomor telepon atau password salah.', 401);
        }

        if ($user->role === 'admin') {
            throw new ApiException('Akun admin tidak bisa login lewat aplikasi.', 403);
        }

        if ($user->account_status !== 'active') {
            throw new ApiException('Akun Anda tidak aktif. Hubungi admin.', 403);
        }

        return [$user, $this->createToken($user)];
    }

    private function createToken(User $user): string
    {
        return $user->createToken('mobile')->plainTextToken;
    }
}