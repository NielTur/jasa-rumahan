<?php

namespace Tests\Feature\Order;

use App\Models\MitraProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes P4c (B-13): expiry pembayaran, auto-complete, dan lazy expiry (B-15).
// Waktu DIBEKUKAN di setUp lalu dimajukan dengan Carbon::setTestNow (tanpa sleep).
// Batas dikunci di setUp: bayar 30 menit (D-06), auto-complete 24 jam (D-07).
class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    private const TELEPON = '081311111111'; // telepon bawaan makeOrder()

    private MitraProfile $mitra;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now()); // bekukan, supaya selisih waktu pasti
        config([
            'marketplace.payment_expiry_minutes' => 30,
            'marketplace.auto_complete_hours' => 24,
        ]);

        $this->seedFixtures();
        $this->mitra = $this->makeMitra('081300000011');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------- helper ----------

    private function svc(): OrderStatusService
    {
        return app(OrderStatusService::class);
    }

    private function majukanMenit(int $menit): void
    {
        Carbon::setTestNow(Carbon::now()->addMinutes($menit));
    }

    // Order waiting_payment dengan riwayat transisi di "sekarang" (seperti hasil accept mitra)
    private function menungguBayar(): Order
    {
        $order = $this->makeOrder(null, OrderStatus::SEARCHING_MITRA, $this->mitra);

        return $this->svc()->transition(
            $order,
            OrderStatus::WAITING_PAYMENT,
            $this->mitra->user,
            'Mitra menerima pesanan.',
            'mitra'
        );
    }

    // Order mitra_completed dengan riwayat transisi di "sekarang"
    private function selesaiOlehMitra(): Order
    {
        $order = $this->makeOrder(null, OrderStatus::IN_PROGRESS, $this->mitra);

        return $this->svc()->transition(
            $order,
            OrderStatus::MITRA_COMPLETED,
            $this->mitra->user,
            'Mitra menandai pekerjaan selesai.',
            'mitra'
        );
    }

    private function buatPayment(Order $order, string $status, ?Carbon $expiredAt = null): Payment
    {
        $urutan = Payment::where('order_id', $order->id)->count() + 1;

        return Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-' . $urutan,
            'amount' => $order->total_price,
            'payment_status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
            'expired_at' => $expiredAt,
        ]);
    }

    private function jalankanExpiryBayar(): void
    {
        $this->artisan('payments:expire-pending')->assertExitCode(0);
    }

    private function jalankanAutoComplete(): void
    {
        $this->artisan('orders:auto-complete')->assertExitCode(0);
    }

    // ---------- expiry pembayaran (command) ----------

    public function test_order_tanpa_payment_belum_lewat_batas_bayar_tidak_berubah(): void
    {
        $order = $this->menungguBayar();

        $this->majukanMenit(29);
        $this->jalankanExpiryBayar();

        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);
        $this->assertSame(0, $order->statusHistories()->where('new_status', 'cancelled')->count());
    }

    public function test_order_tanpa_payment_lewat_batas_bayar_dibatalkan_sistem_dan_idempotent(): void
    {
        $order = $this->menungguBayar(); // customer tidak pernah membuka halaman bayar: tidak ada baris payments

        $this->majukanMenit(31);
        $this->jalankanExpiryBayar();

        $o = $order->fresh();
        $this->assertSame(OrderStatus::CANCELLED, $o->order_status);
        $this->assertSame('system', $o->cancelled_by);
        $this->assertSame('Batas waktu pembayaran habis.', $o->cancel_reason);
        $this->assertNotNull($o->cancelled_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'waiting_payment',
            'new_status' => 'cancelled',
            'changed_by_user_id' => null, // sistem
        ]);

        // Dijalankan lagi: tidak ada riwayat ganda
        $this->jalankanExpiryBayar();
        $this->assertSame(1, $order->statusHistories()->where('new_status', 'cancelled')->count());
    }

    public function test_payment_pending_lewat_expired_at_jadi_expired_dan_order_dibatalkan(): void
    {
        $order = $this->menungguBayar();
        $payment = $this->buatPayment($order, 'pending', Carbon::now()->addMinutes(10));

        // Belum lewat expired_at: tidak ada yang berubah
        $this->majukanMenit(5);
        $this->jalankanExpiryBayar();
        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);

        // Lewat expired_at (total 11 menit), padahal batas bayar order (30 menit) belum habis
        $this->majukanMenit(6);
        $this->jalankanExpiryBayar();

        $this->assertSame('expired', $payment->fresh()->payment_status);
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->order_status);
        $this->assertSame('system', $order->fresh()->cancelled_by);
    }

    public function test_order_dengan_payment_paid_tidak_dibatalkan_walau_lewat_batas(): void
    {
        // Keadaan janggal (paid tapi order masih waiting_payment) dipakai untuk membuktikan pagar:
        // expiry tidak boleh membatalkan order yang sudah dibayar.
        $order = $this->menungguBayar();
        $payment = $this->buatPayment($order, 'paid');

        $this->majukanMenit(35);
        $this->jalankanExpiryBayar();

        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_payment_pending_pada_order_yang_sudah_dibatalkan_tetap_kedaluwarsa_sendiri(): void
    {
        // D-22: cancel customer membiarkan payment pending, lalu ia expired sendiri
        $order = $this->menungguBayar();
        $payment = $this->buatPayment($order, 'pending', Carbon::now()->addMinutes(10));
        $this->svc()->transition($order, OrderStatus::CANCELLED, null, 'Batal.', 'customer');

        $this->majukanMenit(11);
        $this->jalankanExpiryBayar();

        $this->assertSame('expired', $payment->fresh()->payment_status);
        $o = $order->fresh();
        $this->assertSame(OrderStatus::CANCELLED, $o->order_status);
        $this->assertSame('customer', $o->cancelled_by); // pembatalnya tidak tertimpa sistem
        $this->assertSame(1, $order->statusHistories()->where('new_status', 'cancelled')->count());
    }

    public function test_batas_bayar_dihitung_dari_waktu_waiting_payment_bukan_dari_dibuatnya_order(): void
    {
        // D-24: order dibuat sekarang, tapi mitra baru accept 20 menit kemudian
        $order = $this->makeOrder(null, OrderStatus::SEARCHING_MITRA, $this->mitra);
        $this->majukanMenit(20);
        $this->svc()->transition($order, OrderStatus::WAITING_PAYMENT, $this->mitra->user, 'Mitra menerima pesanan.', 'mitra');

        // 40 menit sejak order dibuat (lewat 30), tapi baru 20 menit sejak waiting_payment
        $this->majukanMenit(20);
        $this->jalankanExpiryBayar();
        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);

        // 31 menit sejak waiting_payment: baru dibatalkan
        $this->majukanMenit(11);
        $this->jalankanExpiryBayar();
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->order_status);
    }

    public function test_order_tanpa_riwayat_status_memakai_updated_at_sebagai_acuan(): void
    {
        // Fixture ini membuat order langsung berstatus waiting_payment, tanpa baris riwayat
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $this->mitra);
        $this->assertSame(0, $order->statusHistories()->count());

        $this->majukanMenit(31);
        $this->jalankanExpiryBayar();

        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->order_status);
    }

    // ---------- auto-complete (command) ----------

    public function test_auto_complete_hanya_setelah_batas_jam_dan_tidak_menyentuh_status_lain(): void
    {
        $order = $this->selesaiOlehMitra();
        $lain = $this->makeOrder(null, OrderStatus::IN_PROGRESS, $this->mitra); // masih dikerjakan

        // 23 jam: belum waktunya
        $this->majukanMenit(23 * 60);
        $this->jalankanAutoComplete();
        $this->assertSame(OrderStatus::MITRA_COMPLETED, $order->fresh()->order_status);
        $this->assertNull($order->fresh()->completed_at);

        // 24 jam lebih: selesai otomatis oleh sistem
        $this->majukanMenit(61);
        $this->jalankanAutoComplete();

        $o = $order->fresh();
        $this->assertSame(OrderStatus::COMPLETED, $o->order_status);
        $this->assertNotNull($o->completed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'mitra_completed',
            'new_status' => 'completed',
            'changed_by_user_id' => null, // sistem
        ]);
        $this->assertStringContainsString(
            'Otomatis selesai',
            $order->statusHistories()->where('new_status', 'completed')->value('note')
        );

        // Idempotent, dan order yang belum mitra_completed tidak tersentuh
        $this->jalankanAutoComplete();
        $this->assertSame(1, $order->statusHistories()->where('new_status', 'completed')->count());
        $this->assertSame(OrderStatus::IN_PROGRESS, $lain->fresh()->order_status);
    }

    // ---------- lazy expiry (B-15), tanpa menjalankan command ----------

    public function test_lacak_guest_membereskan_batas_bayar_yang_lewat(): void
    {
        $order = $this->menungguBayar();
        $this->majukanMenit(31);

        $this->postJson('/api/orders/track', [
            'order_code' => $order->order_code,
            'phone' => self::TELEPON,
        ])
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.tracking_stage', 'cancelled');

        $this->assertSame(1, $order->statusHistories()->where('new_status', 'cancelled')->count());
    }

    public function test_cek_status_bayar_membuat_payment_lewat_batas_jadi_expired(): void
    {
        $order = $this->menungguBayar();
        $this->buatPayment($order, 'pending', Carbon::now()->addMinutes(10));
        $this->majukanMenit(11);

        $this->getJson("/api/orders/{$order->order_code}/payment?phone=" . self::TELEPON)
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.payment.payment_status', 'expired');
    }

    public function test_endpoint_mitra_membereskan_order_kedaluwarsa_sebelum_difilter_dan_aksi_ditolak(): void
    {
        $a = $this->menungguBayar();
        $b = $this->menungguBayar();
        $this->majukanMenit(31);

        $this->loginSebagai($this->mitra);

        // Detail lewat find(): order b terbaca cancelled, dan berangkat ditolak 409
        $this->getJson("/api/mitra/orders/{$b->order_code}")
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled');
        $this->postJson("/api/mitra/orders/{$b->order_code}/depart")->assertStatus(409);

        // Daftar: order a (belum disentuh) dibereskan SEBELUM filter, jadi tidak lagi muncul sebagai waiting_payment
        $this->getJson('/api/mitra/orders?status=waiting_payment')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/mitra/orders?status=cancelled')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(OrderStatus::CANCELLED, $a->fresh()->order_status);
    }

    public function test_riwayat_customer_dan_status_history_membereskan_auto_complete(): void
    {
        $customer = User::create([
            'full_name' => 'Customer Tes',
            'phone' => '081300000077',
            'password' => Hash::make('rahasia123'),
            'role' => 'customer',
            'account_status' => 'active',
        ]);

        $order = $this->makeOrder(null, OrderStatus::IN_PROGRESS, $this->mitra);
        $order->update(['customer_id' => $customer->id]);
        $this->svc()->transition($order, OrderStatus::MITRA_COMPLETED, $this->mitra->user, 'Selesai.', 'mitra');

        $this->majukanMenit(24 * 60 + 5);

        Sanctum::actingAs($customer, ['*']);

        $this->getJson('/api/customer/orders')
            ->assertOk()
            ->assertJsonPath('data.0.order_status', 'completed')
            ->assertJsonPath('data.0.tracking_stage', 'done');

        $this->getJson("/api/orders/{$order->order_code}/status-history")
            ->assertOk()
            ->assertJsonCount(2, 'data') // mitra_completed, completed
            ->assertJsonPath('data.1.new_status', 'completed');
    }
}