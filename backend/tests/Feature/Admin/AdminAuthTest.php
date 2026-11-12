<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tes T3a (B-13, A-1): login admin lewat session, pagar role, logout.
// Catatan: halaman setelah login diuji dengan actingAs(), bukan dengan cookie sesi nyata antar-request.
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role, string $phone, string $status = 'active'): User
    {
        return User::create([
            'full_name' => ucfirst($role) . ' Tes',
            'phone' => $phone,
            'password' => Hash::make('admin12345'),
            'role' => $role,
            'account_status' => $status,
        ]);
    }

    public function test_halaman_login_tampil_dan_tamu_dialihkan_ke_login(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Masuk Admin');

        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/admin')->assertRedirect('/admin/dashboard');
    }

    public function test_login_admin_berhasil_dengan_telepon_plus62_dan_yang_sudah_login_dialihkan(): void
    {
        $admin = $this->buatUser('admin', '081200000001');

        $this->post('/admin/login', ['phone' => '+6281200000001', 'password' => 'admin12345'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_sudah_login_membuka_halaman_login_dialihkan_ke_dashboard(): void
    {
        $admin = $this->buatUser('admin', '081200000001');

        $this->actingAs($admin)->get('/admin/login')->assertRedirect('/admin/dashboard');
    }

    public function test_login_gagal_untuk_password_salah_nomor_asing_non_admin_dan_admin_nonaktif(): void
    {
        $this->buatUser('admin', '081200000001');
        $this->buatUser('customer', '081200000011');
        $this->buatUser('mitra', '081200000021');
        $this->buatUser('admin', '081200000002', 'suspended');

        $kasus = [
            ['081200000001', 'salahsalah'],  // password salah
            ['089999999999', 'admin12345'],  // nomor tidak terdaftar
            ['081200000011', 'admin12345'],  // customer, password benar
            ['081200000021', 'admin12345'],  // mitra, password benar
            ['081200000002', 'admin12345'],  // admin suspended, password benar
        ];

        foreach ($kasus as [$telepon, $password]) {
            $this->from('/admin/login')
                ->post('/admin/login', ['phone' => $telepon, 'password' => $password])
                ->assertRedirect('/admin/login')
                ->assertSessionHasErrors('phone');

            $this->assertGuest();
        }

        // Password kosong -> error validasi di field password
        $this->from('/admin/login')
            ->post('/admin/login', ['phone' => '081200000001'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('password');
    }

    public function test_non_admin_dan_admin_nonaktif_tidak_bisa_membuka_dashboard_403_dan_logout_berfungsi(): void
    {
        $customer = $this->buatUser('customer', '081200000011');
        $mitra = $this->buatUser('mitra', '081200000021');
        $suspended = $this->buatUser('admin', '081200000002', 'suspended');
        $admin = $this->buatUser('admin', '081200000001');

        $this->actingAs($customer)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($mitra)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($suspended)->get('/admin/dashboard')->assertStatus(403);

        // Admin aktif boleh, lalu logout
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }
}