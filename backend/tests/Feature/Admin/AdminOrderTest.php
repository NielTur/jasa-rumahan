<?php

namespace Tests\Feature\Admin;

use App\Models\MitraProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\OrderStatusService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes T3d (B-13, D-07, D-08): pesanan dan pembayaran di Admin Dashboard.
// Order dibuat lewat makeOrder(): kode JR-TEST001, JR-TEST002, ... (urut per tes), harga 150000.
class AdminOrderTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    private User $admin;
    private MitraProfile $mitra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();

        $this->admin = User::create([
            'full_name' => 'Admin Tes',
            'phone' => '081200000001',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);
        $this->mitra = $this->makeMitra('081300000011'); // nama "Mitra 081300000011"
    }

    private function buatPayment(Order $order, string $status): Payment
    {
        $urutan = Payment::where('order_id', $order->id)->count() + 1;
        $adaRefund = in_array($status, ['refund_pending', 'refunded', 'refund_failed'], true);
        $sudahBayar = $adaRefund || $status === 'paid';

        return Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-' . $urutan,
            'amount' => $order->total_price,
            'payment_method' => 'bank_transfer',
            'payment_status' => $status,
            'paid_at' => $sudahBayar ? now() : null,
            'refund_amount' => $adaRefund ? $order->total_price : null,
        ]);
    }

    public function test_hanya_admin_yang_bisa_membuka_pesanan_dan_pembayaran(): void
    {
        $order = $this->makeOrder(null, OrderStatus::MITRA_COMPLETED, $this->mitra);
        $payment = $this->buatPayment($order, 'refund_pending');
        $urls = ['/admin/orders', '/admin/payments', "/admin/orders/{$order->id}"];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }

        $customer = User::create([
            'full_name' => 'Customer Tes',
            'phone' => '081300000077',
            'password' => Hash::make('rahasia123'),
            'role' => 'customer',
            'account_status' => 'active',
        ]);

        foreach ($urls as $url) {
            $this->actingAs($customer)->get($url)->assertStatus(403);
        }
        $this->actingAs($customer)->post("/admin/orders/{$order->id}/force-complete")->assertStatus(403);
        $this->actingAs($customer)
            ->patch("/admin/payments/{$payment->id}/refund", ['status' => 'refunded'])
            ->assertStatus(403);

        $this->assertSame(OrderStatus::MITRA_COMPLETED, $order->fresh()->order_status);
        $this->assertSame('refund_pending', $payment->fresh()->payment_status);
    }

    public function test_daftar_pesanan_bisa_difilter_per_status_dan_dicari(): void
    {
        $this->makeOrder(null, OrderStatus::CONFIRMED, $this->mitra);  // JR-TEST001
        $this->makeOrder(null, OrderStatus::COMPLETED, $this->mitra);   // JR-TEST002
        $this->makeOrder(null, OrderStatus::CANCELLED);                 // JR-TEST003

        $this->actingAs($this->admin);

        $this->get('/admin/orders')
            ->assertOk()
            ->assertSee('JR-TEST001')
            ->assertSee('JR-TEST002')
            ->assertSee('JR-TEST003')
            ->assertSee('Budi Tes')
            ->assertSee('Home Cleaning')
            ->assertSee('Rp 150.000')
            ->assertSee('Mitra 081300000011');

        $this->get('/admin/orders?status=confirmed')
            ->assertOk()
            ->assertSee('JR-TEST001')
            ->assertDontSee('JR-TEST002')
            ->assertDontSee('JR-TEST003');

        $this->get('/admin/orders?q=TEST003')
            ->assertOk()
            ->assertSee('JR-TEST003')
            ->assertDontSee('JR-TEST001');

        $this->get('/admin/orders?q=081311111111')->assertOk()->assertSee('JR-TEST002'); // cari lewat telepon
        $this->get('/admin/orders?q=tidakadayangcocok')->assertOk()->assertSee('Tidak ada pesanan yang cocok.');

        $this->get('/admin/orders?status=ngawur')->assertSessionHasErrors('status');
    }

    public function test_detail_pesanan_menampilkan_rincian_pembayaran_dan_riwayat_status(): void
    {
        $order = $this->makeOrder(null, OrderStatus::SEARCHING_MITRA, $this->mitra);
        app(OrderStatusService::class)->transition(
            $order,
            OrderStatus::WAITING_PAYMENT,
            $this->mitra->user,
            'Mitra menerima pesanan.',
            'mitra'
        );
        $this->buatPayment($order, 'paid');

        $this->actingAs($this->admin);

        $this->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee('JR-TEST001')
            ->assertSee('Home Cleaning')
            ->assertSee('Rumah Sedang')
            ->assertSee('Jl. Rahasia No. 99, Bekasi')
            ->assertSee('Guest')
            ->assertSee('Rp 150.000')
            ->assertSee('Mitra 081300000011')
            ->assertSee('JR-TEST001-1')   // ID gateway pembayaran
            ->assertSee('Lunas')
            ->assertSee('Mitra menerima pesanan.')   // catatan di riwayat status
            ->assertDontSee('Paksa selesai');        // bukan mitra_completed

        $this->get('/admin/orders/999999')->assertStatus(404);
    }

    public function test_paksa_selesai_dari_mitra_completed_tercatat_atas_nama_admin_dan_tidak_bisa_dua_kali(): void
    {
        $order = $this->makeOrder(null, OrderStatus::MITRA_COMPLETED, $this->mitra);
        $show = "/admin/orders/{$order->id}";

        $this->actingAs($this->admin);
        $this->get($show)->assertOk()->assertSee('Paksa selesai');

        $this->from($show)
            ->post("/admin/orders/{$order->id}/force-complete")
            ->assertRedirect($show)
            ->assertSessionHas('success');

        $o = $order->fresh();
        $this->assertSame(OrderStatus::COMPLETED, $o->order_status);
        $this->assertNotNull($o->completed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'mitra_completed',
            'new_status' => 'completed',
            'changed_by_user_id' => $this->admin->id,
            'note' => 'Dipaksa selesai oleh admin.',
        ]);

        // Kedua kali ditolak, riwayat tidak dobel, dan tombolnya hilang
        $this->from($show)
            ->post("/admin/orders/{$order->id}/force-complete")
            ->assertRedirect($show)
            ->assertSessionHas('error');
        $this->assertSame(1, $order->statusHistories()->where('new_status', 'completed')->count());
        $this->get($show)->assertOk()->assertDontSee('Paksa selesai');
    }

    public function test_paksa_selesai_dari_status_lain_ditolak_dan_pesanan_tidak_berubah(): void
    {
        $this->actingAs($this->admin);

        $status = [
            OrderStatus::SEARCHING_MITRA,
            OrderStatus::WAITING_PAYMENT,
            OrderStatus::CONFIRMED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::COMPLETED,
            OrderStatus::CANCELLED,
        ];

        foreach ($status as $s) {
            $order = $this->makeOrder(null, $s, $this->mitra);

            $this->from("/admin/orders/{$order->id}")
                ->post("/admin/orders/{$order->id}/force-complete")
                ->assertSessionHas('error');

            $this->assertSame($s, $order->fresh()->order_status);
        }

        $this->post('/admin/orders/999999/force-complete')->assertStatus(404);
        $this->assertSame(0, \App\Models\OrderStatusHistory::count());
    }

    public function test_daftar_pembayaran_bisa_difilter_dan_tombol_refund_hanya_untuk_refund(): void
    {
        $o1 = $this->makeOrder(null, OrderStatus::CONFIRMED, $this->mitra);          // JR-TEST001
        $o2 = $this->makeOrder(null, OrderStatus::CANCELLED, $this->mitra);          // JR-TEST002
        $o3 = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $this->mitra);    // JR-TEST003
        $this->buatPayment($o1, 'paid');
        $this->buatPayment($o2, 'refund_pending');
        $this->buatPayment($o3, 'expired');

        $this->actingAs($this->admin);

        $this->get('/admin/payments')
            ->assertOk()
            ->assertSee('JR-TEST001-1')
            ->assertSee('JR-TEST002-1')
            ->assertSee('JR-TEST003-1')
            ->assertSee('Lunas')
            ->assertSee('Refund menunggu');

        $this->get('/admin/payments?status=refund_pending')
            ->assertOk()
            ->assertSee('JR-TEST002-1')
            ->assertDontSee('JR-TEST001-1')
            ->assertSee('Tandai refunded');

        $this->get('/admin/payments?status=paid')
            ->assertOk()
            ->assertSee('JR-TEST001-1')
            ->assertDontSee('Tandai refunded');

        $this->get('/admin/payments?q=TEST003')
            ->assertOk()
            ->assertSee('JR-TEST003-1')
            ->assertDontSee('JR-TEST002-1');

        $this->get('/admin/payments?status=ngawur')->assertSessionHasErrors('status');
    }

    public function test_tandai_refunded_mengisi_waktu_tidak_mengubah_order_dan_menurunkan_hitungan_dashboard(): void
    {
        $order = $this->makeOrder(null, OrderStatus::CANCELLED, $this->mitra);
        $payment = $this->buatPayment($order, 'refund_pending');

        $this->assertSame(1, app(AdminDashboardService::class)->summary()['refund_pending_count']);

        $this->actingAs($this->admin);

        $this->from('/admin/payments')
            ->patch("/admin/payments/{$payment->id}/refund", ['status' => 'refunded'])
            ->assertRedirect('/admin/payments')
            ->assertSessionHas('success');

        $p = $payment->fresh();
        $this->assertSame('refunded', $p->payment_status);
        $this->assertNotNull($p->refunded_at);
        $this->assertSame(150000, $p->refund_amount);
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->order_status); // status pesanan tidak berubah
        $this->assertSame(0, app(AdminDashboardService::class)->summary()['refund_pending_count']);

        // Yang sudah refunded tidak bisa diubah lagi
        foreach (['refunded', 'refund_failed'] as $lagi) {
            $this->from('/admin/payments')
                ->patch("/admin/payments/{$payment->id}/refund", ['status' => $lagi])
                ->assertSessionHas('error');
        }
        $this->assertSame('refunded', $payment->fresh()->payment_status);
    }

    public function test_refund_gagal_boleh_ditandai_refunded_dan_status_tidak_valid_ditolak(): void
    {
        $order = $this->makeOrder(null, OrderStatus::CANCELLED, $this->mitra);
        $refund = $this->buatPayment($order, 'refund_pending');

        $lunas = $this->makeOrder(null, OrderStatus::CONFIRMED, $this->mitra);
        $paid = $this->buatPayment($lunas, 'paid');

        $this->actingAs($this->admin);

        // refund_pending -> refund_failed -> refunded
        $this->from('/admin/payments')
            ->patch("/admin/payments/{$refund->id}/refund", ['status' => 'refund_failed'])
            ->assertSessionHas('success');
        $this->assertSame('refund_failed', $refund->fresh()->payment_status);
        $this->assertNull($refund->fresh()->refunded_at);

        $this->from('/admin/payments')
            ->patch("/admin/payments/{$refund->id}/refund", ['status' => 'refunded'])
            ->assertSessionHas('success');
        $this->assertSame('refunded', $refund->fresh()->payment_status);
        $this->assertNotNull($refund->fresh()->refunded_at);

        // Pembayaran lunas (bukan refund) tidak bisa ditandai refund apa pun
        foreach (['refunded', 'refund_failed'] as $ke) {
            $this->from('/admin/payments')
                ->patch("/admin/payments/{$paid->id}/refund", ['status' => $ke])
                ->assertSessionHas('error');
        }
        $this->assertSame('paid', $paid->fresh()->payment_status);

        // Nilai di luar refunded/refund_failed ditolak validasi, id tidak ada = 404
        foreach (['pending', 'paid', ''] as $salah) {
            $this->from('/admin/payments')
                ->patch("/admin/payments/{$paid->id}/refund", ['status' => $salah])
                ->assertSessionHasErrors('status');
        }
        $this->patch('/admin/payments/999999/refund', ['status' => 'refunded'])->assertStatus(404);
    }
}