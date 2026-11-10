<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderMitraAttempt;
use App\Models\Payment;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes P4a (B-13): batal, konfirmasi selesai, cek status bayar oleh customer/guest.
// Order dibuat lewat makeOrder(): guest, telepon 081311111111.
class CustomerOrderActionTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    private const TELEPON = '081311111111'; // sama dengan makeOrder()

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
    }

    // ---------- helper ----------

    private function buatPayment(Order $order, string $status = 'paid'): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => $order->total_price,
            'payment_method' => 'bank_transfer',
            'payment_status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }

    private function buatCustomer(string $phone): User
    {
        return User::create([
            'full_name' => 'Customer ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('rahasia123'),
            'role' => 'customer',
            'account_status' => 'active',
        ]);
    }

    // ---------- cancel ----------

    public function test_guest_batal_saat_mencari_mitra_dan_tawaran_menggantung_ditutup(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder();
        $tawaran = OrderMitraAttempt::create([
            'order_id' => $order->id,
            'mitra_id' => $mitra->id,
            'attempt_number' => 1,
            'status' => 'offered',
            'offered_at' => now(),
            'responded_at' => null,
        ]);

        $this->postJson("/api/orders/{$order->order_code}/cancel", [
            'phone' => self::TELEPON,
            'reason' => 'Salah pilih jadwal',
        ])
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.tracking_stage', 'cancelled');

        $order = $order->fresh();
        $this->assertSame('customer', $order->cancelled_by);
        $this->assertSame('Salah pilih jadwal', $order->cancel_reason);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('expired', $tawaran->fresh()->status); // tawaran mitra ikut ditutup
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'new_status' => 'cancelled',
            'changed_by_user_id' => null, // guest
        ]);
    }

    public function test_guest_salah_telepon_404_dan_order_tidak_berubah(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => '089999999999'])
            ->assertStatus(404);

        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
    }

    public function test_guest_tanpa_telepon_404(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->order_code}/cancel")->assertStatus(404);

        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
    }

    public function test_batal_saat_menunggu_bayar_tanpa_payment_tidak_ada_refund(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);

        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => self::TELEPON])
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.payment', null);

        $this->assertSame(0, Payment::count());
    }

    public function test_batal_dengan_payment_pending_membiarkannya_pending(): void
    {
        // Customer sempat membuka halaman bayar tapi belum membayar. Penanganannya di P4b.
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);
        $payment = $this->buatPayment($order, 'pending');

        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => self::TELEPON])->assertOk();

        $this->assertSame('pending', $payment->fresh()->payment_status);
    }

    public function test_batal_setelah_bayar_menandai_refund_pending(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $mitra);
        $payment = $this->buatPayment($order, 'paid');

        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => self::TELEPON])
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.payment.payment_status', 'refund_pending');

        $payment = $payment->fresh();
        $this->assertSame('refund_pending', $payment->payment_status);
        $this->assertSame(150000, $payment->refund_amount); // nominal penuh, tanpa biaya pembatalan
    }

    public function test_batal_saat_pengerjaan_berjalan_409_dan_payment_tidak_berubah(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::IN_PROGRESS, $mitra);
        $payment = $this->buatPayment($order, 'paid');

        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => self::TELEPON])
            ->assertStatus(409);

        $this->assertSame(OrderStatus::IN_PROGRESS, $order->fresh()->order_status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_batal_dua_kali_yang_kedua_409_dan_riwayat_tidak_dobel(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => self::TELEPON])->assertOk();
        $this->postJson("/api/orders/{$order->order_code}/cancel", ['phone' => self::TELEPON])->assertStatus(409);

        $this->assertSame(1, $order->statusHistories()->where('new_status', 'cancelled')->count());
    }

    public function test_customer_login_batal_tanpa_telepon_dan_tercatat_atas_namanya(): void
    {
        $customer = $this->buatCustomer('081300000077');
        $order = $this->makeOrder();
        $order->update(['customer_id' => $customer->id]);

        Sanctum::actingAs($customer, ['*']);
        $this->postJson("/api/orders/{$order->order_code}/cancel")
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'new_status' => 'cancelled',
            'changed_by_user_id' => $customer->id,
        ]);
    }

    public function test_customer_login_lain_tidak_boleh_membatalkan_pesanan_orang_lain_404(): void
    {
        $pemilik = $this->buatCustomer('081300000077');
        $orang = $this->buatCustomer('081300000078');
        $order = $this->makeOrder();
        $order->update(['customer_id' => $pemilik->id]);

        Sanctum::actingAs($orang, ['*']);
        $this->postJson("/api/orders/{$order->order_code}/cancel")->assertStatus(404);

        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
    }

    public function test_alasan_terlalu_panjang_422(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->order_code}/cancel", [
            'phone' => self::TELEPON,
            'reason' => str_repeat('x', 300),
        ])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
    }

    // ---------- confirm-completion ----------

    public function test_guest_konfirmasi_selesai_dari_mitra_completed(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::MITRA_COMPLETED, $mitra);

        $this->postJson("/api/orders/{$order->order_code}/confirm-completion", ['phone' => self::TELEPON])
            ->assertOk()
            ->assertJsonPath('data.order_status', 'completed')
            ->assertJsonPath('data.tracking_stage', 'done');

        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'mitra_completed',
            'new_status' => 'completed',
        ]);
    }

    public function test_konfirmasi_sebelum_mitra_menandai_selesai_409(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::IN_PROGRESS, $mitra);

        $this->postJson("/api/orders/{$order->order_code}/confirm-completion", ['phone' => self::TELEPON])
            ->assertStatus(409);

        $this->assertSame(OrderStatus::IN_PROGRESS, $order->fresh()->order_status);
        $this->assertNull($order->fresh()->completed_at);
    }

    public function test_konfirmasi_dua_kali_yang_kedua_409(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::MITRA_COMPLETED, $mitra);

        $this->postJson("/api/orders/{$order->order_code}/confirm-completion", ['phone' => self::TELEPON])->assertOk();
        $this->postJson("/api/orders/{$order->order_code}/confirm-completion", ['phone' => self::TELEPON])->assertStatus(409);

        $this->assertSame(1, $order->statusHistories()->where('new_status', 'completed')->count());
    }

    // ---------- GET payment ----------

    public function test_status_bayar_tanpa_payment_mengembalikan_payment_null_bukan_error(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);

        $this->getJson("/api/orders/{$order->order_code}/payment?phone=" . self::TELEPON)
            ->assertOk()
            ->assertJsonPath('data.order_code', $order->order_code)
            ->assertJsonPath('data.order_status', 'waiting_payment')
            ->assertJsonPath('data.tracking_stage', 'waiting')
            ->assertJsonPath('data.payment', null);
    }

    public function test_status_bayar_menampilkan_payment_terbaru(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CONFIRMED, $mitra);
        $this->buatPayment($order, 'paid');

        $this->getJson("/api/orders/{$order->order_code}/payment?phone=" . self::TELEPON)
            ->assertOk()
            ->assertJsonPath('data.order_status', 'confirmed')
            ->assertJsonPath('data.payment.payment_status', 'paid')
            ->assertJsonPath('data.payment.amount', 150000)
            ->assertJsonPath('data.payment.payment_method', 'bank_transfer');
    }

    public function test_status_bayar_telepon_salah_404(): void
    {
        $order = $this->makeOrder();

        $this->getJson("/api/orders/{$order->order_code}/payment?phone=089999999999")->assertStatus(404);
    }
}