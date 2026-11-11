<?php

namespace Tests\Feature\Auth;

use App\Models\MitraProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tes catch-up B-13 untuk P1: register, login, logout, me.
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function dataCustomer(array $override = []): array
    {
        return array_merge([
            'full_name' => 'Customer Tes',
            'phone' => '081300000001',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'address' => [
                'label' => 'Rumah',
                'full_address' => 'Jl. Tes No. 1, Bekasi',
                'city' => 'Bekasi',
            ],
        ], $override);
    }

    private function dataMitra(array $override = []): array
    {
        return array_merge([
            'full_name' => 'Mitra Tes',
            'phone' => '081300000011',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $override);
    }

    public function test_register_customer_berhasil_dengan_token_dan_alamat_utama(): void
    {
        $res = $this->postJson('/api/auth/register/customer', $this->dataCustomer())
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonPath('data.user.phone', '081300000001')
            ->assertJsonPath('data.user.account_status', 'active')
            ->assertJsonStructure(['data' => ['token', 'user' => ['user_id', 'full_name', 'phone', 'role']]]);

        $this->assertNotEmpty($res->json('data.token'));
        $this->assertArrayNotHasKey('mitra_id', $res->json('data')); // customer tidak punya mitra_id

        $user = User::where('phone', '081300000001')->firstOrFail();
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertNotSame('rahasia123', $user->password); // password tersimpan sebagai hash

        // Alamat saat register jadi alamat utama
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $user->id,
            'full_address' => 'Jl. Tes No. 1, Bekasi',
            'is_primary' => 1,
        ]);
    }

    public function test_register_mitra_membuat_profil_dan_mengikuti_auto_approve(): void
    {
        config(['marketplace.mitra_auto_approve' => true]);

        $res = $this->postJson('/api/auth/register/mitra', $this->dataMitra())
            ->assertStatus(201)
            ->assertJsonPath('data.user.role', 'mitra')
            ->assertJsonPath('data.user.verification_status', 'approved');

        $profil = MitraProfile::firstOrFail();
        $user = User::where('phone', '081300000011')->firstOrFail();

        // mitra_id = mitra_profiles.id, dan terhubung ke user yang benar (B-11)
        $this->assertEquals($profil->id, $res->json('data.mitra_id'));
        $this->assertEquals($profil->id, $res->json('data.user.mitra_id'));
        $this->assertEquals($user->id, $profil->user_id);

        // D-15: auto approve dimatikan -> mitra baru berstatus pending
        config(['marketplace.mitra_auto_approve' => false]);

        $this->postJson('/api/auth/register/mitra', $this->dataMitra(['phone' => '081300000012']))
            ->assertStatus(201)
            ->assertJsonPath('data.user.verification_status', 'pending');
    }

    public function test_register_telepon_duplikat_ditolak_422_termasuk_bentuk_plus62(): void
    {
        $this->postJson('/api/auth/register/customer', $this->dataCustomer())->assertStatus(201);

        // +6281300000001 dinormalisasi jadi 081300000001 (D-14), jadi bentrok dengan akun pertama
        $this->postJson('/api/auth/register/customer', $this->dataCustomer([
            'full_name' => 'Orang Lain',
            'phone' => '+6281300000001',
        ]))->assertStatus(422)->assertJsonValidationErrors(['phone']);

        // Satu telepon = satu akun, lintas role
        $this->postJson('/api/auth/register/mitra', $this->dataMitra(['phone' => '081300000001']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        $this->assertSame(1, User::count());
        $this->assertSame(0, MitraProfile::count());
    }

    public function test_register_data_tidak_valid_422_dengan_key_error_bertitik(): void
    {
        $this->postJson('/api/auth/register/customer', [
            'full_name' => '',
            'phone' => '12345',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
            'address' => ['full_address' => 'Jl. X'], // label alamat sengaja kosong
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data yang dikirim tidak valid.')
            ->assertJsonValidationErrors(['full_name', 'phone', 'password', 'address.label']); // CR-06

        $this->assertSame(0, User::count());
    }

    public function test_login_berhasil_dengan_plus62_dan_mitra_mendapat_mitra_id(): void
    {
        $this->postJson('/api/auth/register/customer', $this->dataCustomer())->assertStatus(201);
        $regMitra = $this->postJson('/api/auth/register/mitra', $this->dataMitra())->assertStatus(201);

        $this->postJson('/api/auth/login', ['phone' => '+6281300000001', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $loginMitra = $this->postJson('/api/auth/login', ['phone' => '081300000011', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'mitra');

        $this->assertEquals($regMitra->json('data.mitra_id'), $loginMitra->json('data.mitra_id'));
        $this->assertEquals($regMitra->json('data.mitra_id'), $loginMitra->json('data.user.mitra_id'));
    }

    public function test_login_gagal_password_salah_nomor_tak_ada_admin_dan_akun_nonaktif(): void
    {
        $this->postJson('/api/auth/register/customer', $this->dataCustomer())->assertStatus(201);

        // Password salah dan nomor tidak ada: pesan SAMA (tidak membocorkan nomor terdaftar atau tidak)
        $salah = $this->postJson('/api/auth/login', ['phone' => '081300000001', 'password' => 'salahsalah'])
            ->assertStatus(401);
        $asing = $this->postJson('/api/auth/login', ['phone' => '089999999999', 'password' => 'rahasia123'])
            ->assertStatus(401);
        $this->assertSame('Nomor telepon atau password salah.', $salah->json('message'));
        $this->assertSame($salah->json('message'), $asing->json('message'));

        // Admin tidak boleh login lewat API (CR-05)
        User::create([
            'full_name' => 'Admin Tes',
            'phone' => '081300000099',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);
        $this->postJson('/api/auth/login', ['phone' => '081300000099', 'password' => 'admin12345'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Akun admin tidak bisa login lewat aplikasi.');

        // Akun suspended ditolak
        User::where('phone', '081300000001')->update(['account_status' => 'suspended']);
        $this->postJson('/api/auth/login', ['phone' => '081300000001', 'password' => 'rahasia123'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda tidak aktif. Hubungi admin.');

        // Password kosong -> 422
        $this->postJson('/api/auth/login', ['phone' => '081300000001'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_me_butuh_token_dan_logout_mencabut_token(): void
    {
        $token = $this->postJson('/api/auth/register/customer', $this->dataCustomer())
            ->assertStatus(201)
            ->json('data.token');

        // Tanpa token -> 401
        $this->getJson('/api/auth/me')->assertStatus(401);

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '081300000001')
            ->assertJsonPath('data.role', 'customer');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        // Dicek lewat tabel, bukan request kedua: guard menyimpan user di memori selama satu tes
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}