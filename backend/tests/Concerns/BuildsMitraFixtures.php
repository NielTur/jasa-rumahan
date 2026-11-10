<?php

namespace Tests\Concerns;

use App\Models\MitraAvailability;
use App\Models\MitraProfile;
use App\Models\Order;
use App\Models\PropertySize;
use App\Models\Service;
use App\Models\User;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

// Data tes bersama untuk tes mitra/order. Data dibuat langsung lewat model supaya
// mitra dan status order bisa dikontrol (matching asli memilih mitra secara acak).
// Panggil seedFixtures() dari setUp() setelah parent::setUp().
trait BuildsMitraFixtures
{
    private Service $service;
    private PropertySize $size;
    private int $urut = 0;

    protected function seedFixtures(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->service = Service::where('service_name', 'Home Cleaning')->firstOrFail();
        $this->size = PropertySize::where('size_name', 'Rumah Sedang')->firstOrFail();

        // 3 user pengisi supaya users.id TIDAK sama dengan mitra_profiles.id (jebakan B-11)
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'full_name' => "Pengisi $i",
                'phone' => '08139000000' . $i,
                'password' => Hash::make('rahasia123'),
                'role' => 'customer',
                'account_status' => 'active',
            ]);
        }
    }

    private function makeMitra(string $phone, string $verification = 'approved'): MitraProfile
    {
        $user = User::create([
            'full_name' => 'Mitra ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('rahasia123'),
            'role' => 'mitra',
            'account_status' => 'active',
        ]);

        $profile = MitraProfile::create([
            'user_id' => $user->id,
            'verification_status' => $verification,
            'is_available' => true,
        ]);
        $profile->setRelation('user', $user);

        $profile->services()->attach($this->service->id, ['status' => 'active']);

        // Jadwal tiap hari 08-20, supaya tes tidak bergantung hari apa tes dijalankan
        for ($hari = 0; $hari <= 6; $hari++) {
            MitraAvailability::create([
                'mitra_id' => $profile->id,
                'day_of_week' => $hari,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_active' => true,
            ]);
        }

        return $profile;
    }

    private function jadwalBesok(): Carbon
    {
        return Carbon::tomorrow(config('app.timezone'))->setTime(10, 0);
    }

    private function makeOrder(
        ?Carbon $jadwal = null,
        string $status = OrderStatus::SEARCHING_MITRA,
        ?MitraProfile $mitra = null,
    ): Order {
        $this->urut++;

        return Order::create([
            'order_code' => 'JR-TEST' . str_pad((string) $this->urut, 3, '0', STR_PAD_LEFT),
            'customer_id' => null,
            'mitra_id' => $mitra?->id,
            'service_id' => $this->service->id,
            'property_size_id' => $this->size->id,
            'scheduled_at' => $jadwal ?? $this->jadwalBesok(),
            'duration_minutes' => 120,
            'customer_name' => 'Budi Tes',
            'customer_phone' => '081311111111',
            'service_address' => 'Jl. Rahasia No. 99, Bekasi',
            'service_price' => 150000,
            'addons_total' => 0,
            'total_price' => 150000,
            'order_status' => $status,
        ]);
    }

    private function loginSebagai(MitraProfile $mitra): void
    {
        Sanctum::actingAs($mitra->user, ['*']);
    }
}