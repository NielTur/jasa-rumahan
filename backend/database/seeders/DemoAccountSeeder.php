<?php

namespace Database\Seeders;

use App\Models\CustomerAddress;
use App\Models\MitraAvailability;
use App\Models\MitraProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Akun demo supaya demo tidak bergantung input manual (B-14).
// Semua password demo: password123  (HANYA untuk demo lokal)
class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // --- Admin ---
        User::create([
            'full_name' => 'Admin Jasa Rumahan',
            'phone' => '081200000001',
            'password' => $password,
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        // --- 2 Customer + alamat utama ---
        $customers = [
            ['Budi Santoso', '081200000011', 'Jl. Mawar No. 12, Bekasi Timur', 'Bekasi', 'Jawa Barat', '17113'],
            ['Siti Aminah', '081200000012', 'Jl. Melati No. 5, Bekasi Barat', 'Bekasi', 'Jawa Barat', '17134'],
        ];

        foreach ($customers as [$nama, $telp, $alamat, $kota, $provinsi, $kodePos]) {
            $user = User::create([
                'full_name' => $nama,
                'phone' => $telp,
                'password' => $password,
                'role' => 'customer',
                'account_status' => 'active',
            ]);

            CustomerAddress::create([
                'customer_id' => $user->id,
                'address_label' => 'Rumah',
                'recipient_name' => $nama,
                'recipient_phone' => $telp,
                'full_address' => $alamat,
                'city' => $kota,
                'province' => $provinsi,
                'postal_code' => $kodePos,
                'is_primary' => true,
            ]);
        }

        // --- 3 Mitra (approved) ---
        $homeCleaning = Service::where('service_name', 'Home Cleaning')->firstOrFail();
        $lawnMowing = Service::where('service_name', 'Lawn Mowing')->firstOrFail();
        $treeTrimming = Service::where('service_name', 'Tree Trimming')->firstOrFail();

        $mitras = [
            ['Andi Pratama', '081200000021', 'Bekasi dan sekitarnya', [$homeCleaning, $lawnMowing]],
            ['Dewi Lestari', '081200000022', 'Bekasi dan Jakarta Timur', [$homeCleaning, $treeTrimming]],
            ['Rudi Hartono', '081200000023', 'Bekasi, Bogor, Depok', [$homeCleaning, $lawnMowing, $treeTrimming]],
        ];

        foreach ($mitras as [$nama, $telp, $area, $jasaList]) {
            $user = User::create([
                'full_name' => $nama,
                'phone' => $telp,
                'password' => $password,
                'role' => 'mitra',
                'account_status' => 'active',
            ]);

            // mitra_id di seluruh sistem = id dari mitra_profiles ini, bukan users.id (B-11)
            $profil = MitraProfile::create([
                'user_id' => $user->id,
                'bio' => 'Mitra demo berpengalaman.',
                'service_area' => $area,
                'verification_status' => 'approved',
                'is_available' => true,
            ]);

            // Jasa yang disediakan (pivot mitra_services, kolom status wajib diisi)
            foreach ($jasaList as $jasa) {
                $profil->services()->attach($jasa->id, ['status' => 'active']);
            }

            // Jadwal operasional: setiap hari 08.00-20.00 (0 = Minggu ... 6 = Sabtu)
            for ($hari = 0; $hari <= 6; $hari++) {
                MitraAvailability::create([
                    'mitra_id' => $profil->id,
                    'day_of_week' => $hari,
                    'start_time' => '08:00:00',
                    'end_time' => '20:00:00',
                    'is_active' => true,
                ]);
            }
        }
    }
}