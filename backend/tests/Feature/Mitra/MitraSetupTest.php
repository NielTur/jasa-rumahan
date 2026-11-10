<?php

namespace Tests\Feature\Mitra;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Tes P3a: profil, pilih jasa, jadwal mitra.
// Token didapat lewat API register (sesuai kontrak), jadi tidak bergantung pada kolom tabel users.
class MitraSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class); // katalog jasa
    }

    // Daftar mitra baru, kembalikan [token, mitra_id]
    private function registerMitra(string $phone = '081300000001'): array
    {
        $res = $this->postJson('/api/auth/register/mitra', [
            'full_name' => 'Mitra Tes',
            'phone' => $phone,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertStatus(201);

        return [$res->json('data.token'), $res->json('data.mitra_id')];
    }

    private function jadwal(array $rows): array
    {
        return ['availabilities' => $rows];
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/mitra/profile')->assertStatus(401);
    }

    public function test_customer_tidak_boleh_akses_endpoint_mitra_403(): void
    {
        $res = $this->postJson('/api/auth/register/customer', [
            'full_name' => 'Customer Tes',
            'phone' => '081300000099',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'address' => ['label' => 'Rumah', 'full_address' => 'Jl. Tes No. 1, Bekasi'],
        ])->assertStatus(201);

        $this->withToken($res->json('data.token'))
            ->getJson('/api/mitra/profile')
            ->assertStatus(403);
    }

    public function test_profil_menampilkan_user_id_dan_mitra_id_terpisah(): void
    {
        [$token, $mitraId] = $this->registerMitra();

        $this->withToken($token)->getJson('/api/mitra/profile')
            ->assertOk()
            ->assertJsonPath('data.mitra_id', $mitraId)
            ->assertJsonStructure(['data' => ['user_id', 'mitra_id', 'full_name', 'phone', 'bio', 'service_area', 'verification_status', 'is_available']]);
    }

    public function test_mitra_bisa_ubah_profil(): void
    {
        [$token] = $this->registerMitra();

        $this->withToken($token)->putJson('/api/mitra/profile', [
            'bio' => 'Berpengalaman 5 tahun',
            'service_area' => 'Bekasi Barat',
            'is_available' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.bio', 'Berpengalaman 5 tahun')
            ->assertJsonPath('data.service_area', 'Bekasi Barat')
            ->assertJsonPath('data.is_available', false);
    }

    public function test_pilih_jasa_lepas_jasa_dan_pilih_lagi(): void
    {
        [$token] = $this->registerMitra();

        $ids = collect($this->withToken($token)->getJson('/api/mitra/services')->assertOk()->json('data'))->pluck('id');
        $this->assertGreaterThanOrEqual(2, $ids->count(), 'Seeder harus punya minimal 2 jasa aktif');
        [$a, $b] = [$ids[0], $ids[1]];

        // Pilih a dan b
        $res = $this->withToken($token)->putJson('/api/mitra/services', ['service_ids' => [$a, $b]])->assertOk();
        $sel = collect($res->json('data'))->pluck('selected', 'id');
        $this->assertTrue($sel[$a]);
        $this->assertTrue($sel[$b]);

        // Lepas b: harus jadi tidak terpilih (inactive), a tetap
        $res = $this->withToken($token)->putJson('/api/mitra/services', ['service_ids' => [$a]])->assertOk();
        $sel = collect($res->json('data'))->pluck('selected', 'id');
        $this->assertTrue($sel[$a]);
        $this->assertFalse($sel[$b]);

        // Pilih b lagi: pivot inactive harus kembali active, bukan insert baru (UNIQUE)
        $res = $this->withToken($token)->putJson('/api/mitra/services', ['service_ids' => [$a, $b]])->assertOk();
        $sel = collect($res->json('data'))->pluck('selected', 'id');
        $this->assertTrue($sel[$b]);
    }

    public function test_pilih_jasa_yang_tidak_ada_422(): void
    {
        [$token] = $this->registerMitra();

        $this->withToken($token)->putJson('/api/mitra/services', ['service_ids' => [999999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_ids.0']);
    }

    public function test_jadwal_tersimpan_dan_diganti_total(): void
    {
        [$token] = $this->registerMitra();

        $this->withToken($token)->putJson('/api/mitra/availabilities', $this->jadwal([
            ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
            ['day_of_week' => 1, 'start_time' => '12:00', 'end_time' => '16:00'], // bersentuhan, bukan overlap
            ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => false],
        ]))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.start_time', '08:00') // format HH:MM, bukan HH:MM:SS
            ->assertJsonPath('data.0.is_active', true);    // default true

        // Kirim ulang 1 jadwal: yang lama harus hilang
        $this->withToken($token)->putJson('/api/mitra/availabilities', $this->jadwal([
            ['day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '14:00'],
        ]))->assertOk()->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/mitra/availabilities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.day_of_week', 5);
    }

    public function test_jadwal_jam_selesai_tidak_boleh_sebelum_jam_mulai_422(): void
    {
        [$token] = $this->registerMitra();

        $this->withToken($token)->putJson('/api/mitra/availabilities', $this->jadwal([
            ['day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '10:00'],
        ]))->assertStatus(422)->assertJsonValidationErrors(['availabilities.0.end_time']);
    }

    public function test_jadwal_di_luar_jam_operasional_422(): void
    {
        [$token] = $this->registerMitra();

        $this->withToken($token)->putJson('/api/mitra/availabilities', $this->jadwal([
            ['day_of_week' => 1, 'start_time' => '06:00', 'end_time' => '10:00'],
        ]))->assertStatus(422)->assertJsonValidationErrors(['availabilities.0.start_time']);
    }

    public function test_jadwal_overlap_di_hari_yang_sama_422(): void
    {
        [$token] = $this->registerMitra();

        $this->withToken($token)->putJson('/api/mitra/availabilities', $this->jadwal([
            ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '18:00'],
            ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '10:00'],
            ['day_of_week' => 2, 'start_time' => '10:00', 'end_time' => '11:00'], // overlap dengan blok 08-18
        ]))->assertStatus(422)->assertJsonValidationErrors(['availabilities.1.start_time', 'availabilities.2.start_time']);
    }
}