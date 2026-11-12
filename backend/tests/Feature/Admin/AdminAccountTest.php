<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes T3c (B-13, A-1): akun dan mitra di Admin Dashboard, termasuk dampaknya ke API.
class AdminAccountTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures(); // master + 3 customer "Pengisi 1..3" (telepon 081390000001..3)

        $this->admin = $this->buatUser('admin', '081200000001');
    }

    private function buatUser(string $role, string $phone): User
    {
        return User::create([
            'full_name' => ucfirst($role) . ' ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('rahasia123'),
            'role' => $role,
            'account_status' => 'active',
        ]);
    }

    // Apakah slot 10:00 besok tersedia untuk Home Cleaning (butuh minimal 1 mitra eligible)
    private function slot10Tersedia(): bool
    {
        $tanggal = Carbon::tomorrow(config('app.timezone'))->format('Y-m-d');

        return (bool) $this->getJson("/api/slots?service_id={$this->service->id}&date={$tanggal}")
            ->assertOk()
            ->json('data.1.available');
    }

    public function test_hanya_admin_yang_bisa_membuka_akun_dan_mitra(): void
    {
        foreach (['/admin/users', '/admin/mitra'] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }

        $customer = $this->buatUser('customer', '081300000077');

        foreach (['/admin/users', '/admin/mitra'] as $url) {
            $this->actingAs($customer)->get($url)->assertStatus(403);
        }

        $this->actingAs($customer)
            ->patch("/admin/users/{$customer->id}/status", ['status' => 'suspended'])
            ->assertStatus(403);
        $this->assertSame('active', $customer->fresh()->account_status);
    }

    public function test_daftar_akun_bisa_difilter_per_role_status_dan_pencarian(): void
    {
        $mitra = $this->makeMitra('081300000011'); // nama "Mitra 081300000011"
        User::where('phone', '081390000003')->update(['account_status' => 'suspended']);

        $this->actingAs($this->admin);

        $this->get('/admin/users')->assertOk()->assertSee('Pengisi 1')->assertSee('Mitra 081300000011');

        $this->get('/admin/users?role=mitra')
            ->assertOk()
            ->assertSee('Mitra 081300000011')
            ->assertDontSee('Pengisi 1');

        $this->get('/admin/users?q=081390000002')
            ->assertOk()
            ->assertSee('Pengisi 2')
            ->assertDontSee('Pengisi 1');

        $this->get('/admin/users?status=suspended')
            ->assertOk()
            ->assertSee('Pengisi 3')
            ->assertDontSee('Pengisi 1');

        // Filter yang tidak dikenal ditolak
        $this->get('/admin/users?role=superuser')->assertSessionHasErrors('role');
    }

    public function test_suspend_mencabut_token_memblokir_login_dan_aktifkan_membukanya_lagi(): void
    {
        $customer = $this->buatUser('customer', '081300000077');
        $customer->createToken('mobile');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($this->admin);

        $this->from('/admin/users')
            ->patch("/admin/users/{$customer->id}/status", ['status' => 'suspended'])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('success');

        $this->assertSame('suspended', $customer->fresh()->account_status);
        $this->assertDatabaseCount('personal_access_tokens', 0); // token lama dicabut

        $this->postJson('/api/auth/login', ['phone' => '081300000077', 'password' => 'rahasia123'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda tidak aktif. Hubungi admin.');

        $this->from('/admin/users')
            ->patch("/admin/users/{$customer->id}/status", ['status' => 'active'])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('success');

        $this->postJson('/api/auth/login', ['phone' => '081300000077', 'password' => 'rahasia123'])->assertOk();
    }

    public function test_akun_admin_tidak_bisa_diubah_dan_input_tidak_valid_ditolak(): void
    {
        $admin2 = $this->buatUser('admin', '081200000002');
        $customer = $this->buatUser('customer', '081300000077');

        $this->actingAs($this->admin);

        // Admin lain maupun dirinya sendiri tidak bisa disuspend
        foreach ([$admin2->id, $this->admin->id] as $id) {
            $this->from('/admin/users')
                ->patch("/admin/users/{$id}/status", ['status' => 'suspended'])
                ->assertRedirect('/admin/users')
                ->assertSessionHas('error');
        }
        $this->assertSame('active', $admin2->fresh()->account_status);
        $this->assertSame('active', $this->admin->fresh()->account_status);

        // Status di luar active/suspended ditolak
        foreach (['inactive', 'hapus', ''] as $salah) {
            $this->from('/admin/users')
                ->patch("/admin/users/{$customer->id}/status", ['status' => $salah])
                ->assertSessionHasErrors('status');
        }
        $this->assertSame('active', $customer->fresh()->account_status);

        // Akun tidak ada
        $this->patch('/admin/users/999999/status', ['status' => 'active'])->assertStatus(404);
    }

    public function test_persetujuan_mitra_menentukan_apakah_ia_ditawari_pesanan(): void
    {
        $mitra = $this->makeMitra('081300000011', 'pending');
        $this->assertFalse($this->slot10Tersedia()); // mitra pending tidak dihitung

        $this->actingAs($this->admin);

        $this->get('/admin/mitra?status=pending')->assertOk()->assertSee('Mitra 081300000011');
        $this->get('/admin/mitra?status=approved')->assertOk()->assertDontSee('Mitra 081300000011');
        $this->get('/admin/mitra?status=sembarang')->assertSessionHasErrors('status');

        // Setujui -> slot tersedia
        $this->from('/admin/mitra')
            ->patch("/admin/mitra/{$mitra->id}/verification", ['verification_status' => 'approved'])
            ->assertRedirect('/admin/mitra')
            ->assertSessionHas('success');
        $this->assertSame('approved', $mitra->fresh()->verification_status);
        $this->assertTrue($this->slot10Tersedia());

        // Cabut persetujuan -> slot tertutup lagi
        $this->from('/admin/mitra')
            ->patch("/admin/mitra/{$mitra->id}/verification", ['verification_status' => 'rejected'])
            ->assertRedirect('/admin/mitra');
        $this->assertSame('rejected', $mitra->fresh()->verification_status);
        $this->assertFalse($this->slot10Tersedia());

        // Nilai di luar approved/rejected ditolak
        $this->from('/admin/mitra')
            ->patch("/admin/mitra/{$mitra->id}/verification", ['verification_status' => 'pending'])
            ->assertSessionHasErrors('verification_status');
        $this->assertSame('rejected', $mitra->fresh()->verification_status);
    }

    public function test_akun_mitra_yang_disuspend_tidak_lagi_menerima_pesanan_dan_tokennya_dicabut(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $mitra->user->createToken('mobile');
        $this->assertTrue($this->slot10Tersedia());

        $this->actingAs($this->admin);

        $this->from('/admin/users')
            ->patch("/admin/users/{$mitra->user_id}/status", ['status' => 'suspended'])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertFalse($this->slot10Tersedia());

        $this->from('/admin/users')
            ->patch("/admin/users/{$mitra->user_id}/status", ['status' => 'active'])
            ->assertRedirect('/admin/users');
        $this->assertTrue($this->slot10Tersedia());
    }
}