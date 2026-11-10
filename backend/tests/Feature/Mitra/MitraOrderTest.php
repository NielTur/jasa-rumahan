<?php

namespace Tests\Feature\Mitra;

use App\Models\Order;
use App\Models\Payment;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes P3c (B-13): progres status oleh mitra + simulate-paid (khusus dev).
class MitraOrderTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
    }

    // Pembayaran lunas "asli" untuk tes progres (tanpa lewat Midtrans)
    private function bayarLunas(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => $order->total_price,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    // Membalik env aplikasi jadi "local" di dalam tes, untuk menguji jalur sukses simulate-paid
    private function modeLocal(): void
    {
        $this->app->detectEnvironment(fn() => 'local');
    }

    // ---------- daftar dan detail ----------

    public function test_daftar_order_hanya_milik_mitra_ini_dengan_filter_status(): void
    {
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $this->makeOrder(null, OrderStatus::CONFIRMED, $a);
        $this->makeOrder(null, OrderStatus::ON_THE_WAY, $a);
        $this->makeOrder(null, OrderStatus::CONFIRMED, $b);   // milik mitra lain
        $this->makeOrder(null, OrderStatus::SEARCHING_MITRA); // belum punya mitra

        $this->loginSebagai($a);

        $this->getJson('/api/mitra/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/mitra/orders?status=confirmed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_status', 'confirmed');

        $this->getJson('/api/mitra/orders?status=confirmed,on_the_way')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/mitra/orders?status=ngawur')->assertStatus(422);
    }

    public function test_detail_order_menampilkan_alamat_dan_telepon_customer_untuk_mitra_pemilik(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $a);

        $this->loginSebagai($a);
        $this->getJson("/api/mitra/orders/{$order->order_code}")
            ->assertOk()
            ->assertJsonPath('data.order_code', $order->order_code)
            ->assertJsonPath('data.customer.phone', '081311111111')
            ->assertJsonPath('data.service_address', 'Jl. Rahasia No. 99, Bekasi');
    }

    public function test_order_milik_mitra_lain_tidak_bisa_dilihat_atau_diubah_404(): void
    {
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $a);
        $this->bayarLunas($order);

        $this->loginSebagai($b);
        $this->getJson("/api/mitra/orders/{$order->order_code}")->assertStatus(404);
        $this->postJson("/api/mitra/orders/{$order->order_code}/depart")->assertStatus(404);

        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->order_status);
    }

    // ---------- progres status ----------

    public function test_alur_progres_lengkap_dari_berangkat_sampai_selesai(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $a);
        $this->bayarLunas($order);
        $kode = $order->order_code;

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/orders/$kode/depart")->assertOk()
            ->assertJsonPath('data.order_status', 'on_the_way')
            ->assertJsonPath('data.tracking_stage', 'working');
        $this->postJson("/api/mitra/orders/$kode/arrive")->assertOk()
            ->assertJsonPath('data.order_status', 'arrived');
        $this->postJson("/api/mitra/orders/$kode/start")->assertOk()
            ->assertJsonPath('data.order_status', 'in_progress');
        $this->postJson("/api/mitra/orders/$kode/finish")->assertOk()
            ->assertJsonPath('data.order_status', 'mitra_completed')
            ->assertJsonPath('data.tracking_stage', 'awaiting_confirmation');

        // Setiap langkah tercatat di riwayat, pelakunya user mitra (users.id, bukan mitra_id)
        $this->assertSame(
            ['on_the_way', 'arrived', 'in_progress', 'mitra_completed'],
            $order->statusHistories()->orderBy('id')->pluck('new_status')->all()
        );
        $this->assertSame(4, $order->statusHistories()->where('changed_by_user_id', $a->user_id)->count());

        // Finish dobel ditolak, dan riwayat tidak bertambah
        $this->postJson("/api/mitra/orders/$kode/finish")->assertStatus(409);
        $this->assertSame(4, $order->statusHistories()->count());
    }

    public function test_berangkat_ditolak_kalau_belum_ada_pembayaran_paid_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $a); // sengaja tanpa payment

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/orders/{$order->order_code}/depart")->assertStatus(409);

        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->order_status);
    }

    public function test_berangkat_ditolak_saat_masih_menunggu_pembayaran_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $a);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/orders/{$order->order_code}/depart")->assertStatus(409);

        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);
    }

    public function test_loncat_langkah_ditolak_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $a);
        $this->bayarLunas($order);
        $kode = $order->order_code;

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/orders/$kode/arrive")->assertStatus(409);
        $this->postJson("/api/mitra/orders/$kode/start")->assertStatus(409);
        $this->postJson("/api/mitra/orders/$kode/finish")->assertStatus(409);

        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->order_status);
        $this->assertSame(0, $order->statusHistories()->count());
    }

    public function test_order_yang_sudah_dibatalkan_tidak_bisa_diproses_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CANCELLED, $a);
        $this->bayarLunas($order);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/orders/{$order->order_code}/depart")->assertStatus(409);

        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->order_status);
    }

    // ---------- simulate-paid (khusus dev) ----------

    public function test_simulate_paid_di_luar_local_dibalas_404(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $a);

        $this->assertSame('testing', app()->environment()); // env tes BUKAN local

        $this->postJson("/api/dev/orders/{$order->order_code}/simulate-paid")->assertStatus(404);

        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);
        $this->assertSame(0, Payment::count());
    }

    public function test_simulate_paid_di_local_membuat_payment_paid_dan_order_confirmed(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $a);

        $this->modeLocal();
        $this->postJson("/api/dev/orders/{$order->order_code}/simulate-paid")
            ->assertOk()
            ->assertJsonPath('data.order_status', 'confirmed')
            ->assertJsonPath('data.payment.payment_status', 'paid')
            ->assertJsonPath('data.payment.amount', 150000);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'simulator',
            'gateway_order_id' => $order->order_code . '-DEV1',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'waiting_payment',
            'new_status' => 'confirmed',
            'changed_by_user_id' => null, // sistem
        ]);
    }

    public function test_setelah_simulate_paid_mitra_bisa_berangkat(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $a);

        $this->modeLocal();
        $this->postJson("/api/dev/orders/{$order->order_code}/simulate-paid")->assertOk();

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/orders/{$order->order_code}/depart")
            ->assertOk()
            ->assertJsonPath('data.order_status', 'on_the_way');
    }

    public function test_simulate_paid_pada_order_bukan_menunggu_bayar_409(): void
    {
        $order = $this->makeOrder(null, OrderStatus::SEARCHING_MITRA);

        $this->modeLocal();
        $this->postJson("/api/dev/orders/{$order->order_code}/simulate-paid")->assertStatus(409);

        $this->assertSame(0, Payment::count());
    }

    public function test_simulate_paid_dua_kali_yang_kedua_ditolak_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $a);

        $this->modeLocal();
        $this->postJson("/api/dev/orders/{$order->order_code}/simulate-paid")->assertOk();
        $this->postJson("/api/dev/orders/{$order->order_code}/simulate-paid")->assertStatus(409);

        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_simulate_paid_kode_tidak_ada_404(): void
    {
        $this->modeLocal();
        $this->postJson('/api/dev/orders/JR-TIDAKADA/simulate-paid')->assertStatus(404);
    }
}