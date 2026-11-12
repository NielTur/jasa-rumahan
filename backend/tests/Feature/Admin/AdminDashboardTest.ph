<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes T3a: angka ringkasan dashboard admin.
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();

        $mitra = $this->makeMitra('081300000011');
        $this->makeMitra('081300000012', 'pending'); // menunggu persetujuan

        $this->makeOrder(null, OrderStatus::CONFIRMED, $mitra);
        $this->makeOrder(null, OrderStatus::CONFIRMED, $mitra);
        $this->makeOrder(null, OrderStatus::COMPLETED, $mitra);
        $this->makeOrder(null, OrderStatus::CANCELLED);

        // Dibatalkan setelah dibayar -> uang perlu dikembalikan (D-08)
        $batalSetelahBayar = $this->makeOrder(null, OrderStatus::CANCELLED);
        Payment::create([
            'order_id' => $batalSetelahBayar->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $batalSetelahBayar->order_code . '-1',
            'amount' => 150000,
            'payment_status' => 'refund_pending',
            'paid_at' => now(),
            'refund_amount' => 150000,
        ]);
    }

    private function buatAdmin(): User
    {
        return User::create([
            'full_name' => 'Admin Tes',
            'phone' => '081200000001',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);
    }

    public function test_ringkasan_menghitung_pesanan_refund_dan_mitra_menunggu(): void
    {
        $s = app(AdminDashboardService::class)->summary();

        $this->assertSame(5, $s['total_orders']);
        $this->assertSame(2, $s['active_orders']);   // hanya 2 yang confirmed
        $this->assertSame(5, $s['created_today']);
        $this->assertSame(0, $s['scheduled_today']); // semua dijadwalkan besok
        $this->assertSame(1, $s['refund_pending_count']);
        $this->assertSame(150000, $s['refund_pending_amount']);
        $this->assertSame(1, $s['mitra_pending']);

        // Semua status tampil berurutan, termasuk yang 0
        $this->assertCount(10, $s['status_counts']);
        $perStatus = collect($s['status_counts'])->pluck('total', 'status');
        $this->assertSame(2, $perStatus['confirmed']);
        $this->assertSame(1, $perStatus['completed']);
        $this->assertSame(2, $perStatus['cancelled']);
        $this->assertSame(0, $perStatus['searching_mitra']);
    }

    public function test_halaman_dashboard_menampilkan_angka_dan_label(): void
    {
        $this->actingAs($this->buatAdmin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Rp 150.000')
            ->assertSee('Mitra menunggu persetujuan')
            ->assertSee('Dikonfirmasi')
            ->assertSee('Menunggu konfirmasi customer');
    }
}