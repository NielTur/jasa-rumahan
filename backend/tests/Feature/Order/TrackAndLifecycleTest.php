<?php

namespace Tests\Feature\Order;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\OrderMitraAttempt;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\OrderStatusService;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes catch-up B-13 untuk P2: lacak guest + throttle, riwayat status, transisi status, matching.
//
// CATATAN: tes lacak memakai order berstatus waiting_payment (ada mitra). Alasannya: order
// searching_mitra yang dibaca lewat endpoint ikut "dimajukan" matching (lazy expiry, B-15), dan
// kalau tidak ada kandidat mitra order itu langsung dibatalkan sistem. Itu perilaku benar, tapi
// bukan yang sedang diuji di sini.
class TrackAndLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    private const TELEPON = '081311111111'; // telepon bawaan makeOrder()

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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

    private function track(string $kode, string $telepon)
    {
        return $this->postJson('/api/orders/track', ['order_code' => $kode, 'phone' => $telepon]);
    }

    // Majukan waktu melewati batas respons mitra (config, bukan angka literal)
    private function lewatiBatasRespons(): void
    {
        Carbon::setTestNow(Carbon::now()->addSeconds((int) config('marketplace.mitra_response_seconds') + 10));
    }

    private function assertTransisiDitolak(Order $order, string $ke): void
    {
        $dari = $order->order_status;

        try {
            app(OrderStatusService::class)->transition($order, $ke);
            $this->fail("Transisi {$dari} -> {$ke} seharusnya ditolak.");
        } catch (ApiException $e) {
            $this->assertSame(409, $e->status());
            $this->assertSame("Status pesanan tidak bisa diubah dari {$dari} ke {$ke}.", $e->getMessage());
        }
    }

    // ---------- lacak guest ----------

    public function test_guest_bisa_melacak_dengan_kode_dan_telepon_dan_get_order_hanya_untuk_pemilik(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);

        // Kode huruf kecil dan telepon +62 tetap cocok (normalisasi)
        $this->track(strtolower($order->order_code), '+6281311111111')
            ->assertOk()
            ->assertJsonPath('data.order_code', $order->order_code)
            ->assertJsonPath('data.order_status', 'waiting_payment')
            ->assertJsonPath('data.tracking_stage', 'waiting')
            ->assertJsonPath('data.customer.name', 'Budi Tes')
            ->assertJsonPath('data.mitra.mitra_id', $mitra->id);

        // GET /orders/{kode}: khusus customer login yang menjadi pemilik
        $pemilik = $this->buatCustomer('081300000077');
        $orang = $this->buatCustomer('081300000078');
        $order->update(['customer_id' => $pemilik->id]);

        $this->getJson("/api/orders/{$order->order_code}")->assertStatus(401); // tanpa token

        Sanctum::actingAs($pemilik, ['*']);
        $this->getJson("/api/orders/{$order->order_code}")
            ->assertOk()
            ->assertJsonPath('data.order_code', $order->order_code);

        Sanctum::actingAs($orang, ['*']);
        $this->getJson("/api/orders/{$order->order_code}")->assertStatus(404);
    }

    public function test_lacak_gagal_selalu_404_generik_dan_telepon_wajib_diisi(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);

        $teleponSalah = $this->track($order->order_code, '089999999999')->assertStatus(404);
        $kodeAsing = $this->track('JR-TIDAKADA', self::TELEPON)->assertStatus(404);

        // Pesan sama persis: penyerang tidak bisa membedakan "kode tidak ada" dan "telepon salah" (B-9)
        $this->assertSame('Pesanan tidak ditemukan.', $teleponSalah->json('message'));
        $this->assertSame($teleponSalah->json('message'), $kodeAsing->json('message'));

        $this->postJson('/api/orders/track', ['order_code' => $order->order_code])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_lima_percobaan_gagal_mengunci_kode_itu_dengan_429_tapi_kode_lain_aman(): void
    {
        config(['marketplace.guest_throttle' => '5,1']); // 5 percobaan gagal per menit (D-13)

        $mitra = $this->makeMitra('081300000011');
        $a = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);
        $b = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);

        for ($i = 0; $i < 5; $i++) {
            $this->track($a->order_code, '089999999999')->assertStatus(404);
        }

        // Percobaan ke-6 dikunci walaupun teleponnya benar (cek throttle datang lebih dulu)
        $this->track($a->order_code, self::TELEPON)->assertStatus(429);

        // Kunci per IP + kode: order lain dari IP yang sama tidak ikut terkunci
        $this->track($b->order_code, self::TELEPON)->assertOk();
    }

    public function test_polling_yang_berhasil_tidak_pernah_kena_throttle(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT, $mitra);

        // CR-10: hanya percobaan GAGAL yang dihitung. Polling tracking 10 kali tetap 200.
        for ($i = 0; $i < 10; $i++) {
            $this->track($order->order_code, self::TELEPON)->assertOk();
        }
    }

    public function test_riwayat_status_berurutan_baris_pertama_old_status_null_dan_dijaga_telepon(): void
    {
        $mitra = $this->makeMitra('081300000011');
        $svc = app(OrderStatusService::class);

        $order = $this->makeOrder(null, OrderStatus::PENDING, $mitra);
        $svc->logCreated($order);
        $svc->transition($order, OrderStatus::SEARCHING_MITRA, null, 'Mencari mitra.', 'system');
        $svc->transition($order, OrderStatus::WAITING_PAYMENT, $mitra->user, 'Mitra menerima pesanan.', 'mitra');

        $kode = $order->order_code;

        $this->getJson("/api/orders/{$kode}/status-history?phone=" . self::TELEPON)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.old_status', null)          // klaim kontrak 10.10
            ->assertJsonPath('data.0.new_status', 'pending')
            ->assertJsonPath('data.0.note', 'Pesanan dibuat.')
            ->assertJsonPath('data.1.old_status', 'pending')
            ->assertJsonPath('data.1.new_status', 'searching_mitra')
            ->assertJsonPath('data.2.new_status', 'waiting_payment')
            ->assertJsonStructure(['data' => [['old_status', 'new_status', 'note', 'created_at']]]);

        // Telepon format +62 juga diterima
        $this->getJson("/api/orders/{$kode}/status-history?phone=%2B6281311111111")->assertOk();

        // Telepon salah atau tanpa telepon -> 404
        $this->getJson("/api/orders/{$kode}/status-history?phone=089999999999")->assertStatus(404);
        $this->getJson("/api/orders/{$kode}/status-history")->assertStatus(404);
    }

    // ---------- transisi status (B-4) ----------

    public function test_transisi_valid_mengisi_kolom_selesai_dan_batal_serta_tracking_stage(): void
    {
        $svc = app(OrderStatusService::class);
        $order = $this->makeOrder(); // searching_mitra

        $urutan = [
            OrderStatus::WAITING_PAYMENT,
            OrderStatus::CONFIRMED,
            OrderStatus::ON_THE_WAY,
            OrderStatus::ARRIVED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::MITRA_COMPLETED,
            OrderStatus::COMPLETED,
        ];

        foreach ($urutan as $status) {
            $order = $svc->transition($order, $status, null, "Ke {$status}", 'system');
            $this->assertSame($status, $order->order_status);
        }

        $akhir = $order->fresh();
        $this->assertNotNull($akhir->completed_at);
        $this->assertNull($akhir->cancelled_at);

        // Setiap langkah tercatat, berantai dari status sebelumnya
        $riwayat = $order->statusHistories()->orderBy('id')->get();
        $this->assertSame($urutan, $riwayat->pluck('new_status')->all());
        $this->assertSame(
            array_merge([OrderStatus::SEARCHING_MITRA], array_slice($urutan, 0, -1)),
            $riwayat->pluck('old_status')->all()
        );

        // Batal oleh sistem: kolom pembatalan terisi
        $batal = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT);
        $batal = $svc->transition($batal, OrderStatus::CANCELLED, null, 'Pembayaran kedaluwarsa.', 'system');
        $batal = $batal->fresh();
        $this->assertNotNull($batal->cancelled_at);
        $this->assertSame('Pembayaran kedaluwarsa.', $batal->cancel_reason);
        $this->assertSame('system', $batal->cancelled_by);
        $this->assertNull($batal->completed_at);

        // Tanpa actorType, cancelled_by diturunkan dari role user yang membatalkan
        $customer = $this->buatCustomer('081300000077');
        $o2 = $svc->transition($this->makeOrder(null, OrderStatus::WAITING_PAYMENT), OrderStatus::CANCELLED, $customer, 'Batal.');
        $this->assertSame('customer', $o2->fresh()->cancelled_by);

        // Turunan tracking_stage untuk UI customer (kontrak 6.2)
        $peta = [
            OrderStatus::PENDING => 'searching',
            OrderStatus::SEARCHING_MITRA => 'searching',
            OrderStatus::WAITING_PAYMENT => 'waiting',
            OrderStatus::CONFIRMED => 'confirmed',
            OrderStatus::ON_THE_WAY => 'working',
            OrderStatus::ARRIVED => 'working',
            OrderStatus::IN_PROGRESS => 'working',
            OrderStatus::MITRA_COMPLETED => 'awaiting_confirmation',
            OrderStatus::COMPLETED => 'done',
            OrderStatus::CANCELLED => 'cancelled',
        ];
        foreach ($peta as $status => $tahap) {
            $this->assertSame($tahap, OrderStatus::trackingStage($status));
        }
    }

    public function test_transisi_tidak_valid_ditolak_409_tanpa_mengubah_status_atau_riwayat(): void
    {
        $kasus = [
            [OrderStatus::PENDING, OrderStatus::CONFIRMED],
            [OrderStatus::PENDING, OrderStatus::COMPLETED],
            [OrderStatus::SEARCHING_MITRA, OrderStatus::CONFIRMED],
            [OrderStatus::SEARCHING_MITRA, OrderStatus::COMPLETED],
            [OrderStatus::WAITING_PAYMENT, OrderStatus::ON_THE_WAY],
            [OrderStatus::CONFIRMED, OrderStatus::ARRIVED],
            [OrderStatus::CONFIRMED, OrderStatus::COMPLETED],
            [OrderStatus::IN_PROGRESS, OrderStatus::CANCELLED],      // sudah dikerjakan: tidak bisa batal
            [OrderStatus::MITRA_COMPLETED, OrderStatus::CANCELLED],
            [OrderStatus::COMPLETED, OrderStatus::CANCELLED],
            [OrderStatus::CANCELLED, OrderStatus::SEARCHING_MITRA],  // status akhir tidak bisa dihidupkan
            [OrderStatus::CANCELLED, OrderStatus::CONFIRMED],
        ];

        foreach ($kasus as [$dari, $ke]) {
            $order = $this->makeOrder(null, $dari);

            $this->assertTransisiDitolak($order, $ke);
            $this->assertSame($dari, $order->fresh()->order_status);
        }

        // Transisi yang ditolak tidak boleh menulis riwayat sama sekali
        $this->assertSame(0, OrderStatusHistory::count());
    }

    // ---------- matching (B-6) ----------

    public function test_offer_kedaluwarsa_diproses_command_lalu_ditawarkan_ke_mitra_berikutnya(): void
    {
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $order = $this->makeOrder(); // searching_mitra, belum ada penawaran

        app(MatchingService::class)->advance($order);

        // Attempt #1 dibuat ke salah satu mitra (urutan kandidat acak, D-16)
        $pertama = OrderMitraAttempt::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(1, $pertama->attempt_number);
        $this->assertSame('offered', $pertama->status);
        $this->assertTrue(in_array((int) $pertama->mitra_id, [$a->id, $b->id], true));
        $this->assertSame(1, OrderMitraAttempt::where('order_id', $order->id)->count());

        // Mitra tidak merespons sampai lewat batas waktu, lalu scheduler (command) berjalan
        $this->lewatiBatasRespons();
        $this->artisan('orders:process-expired-offers')->assertExitCode(0);

        $this->assertSame('expired', $pertama->fresh()->status);

        $kedua = OrderMitraAttempt::where('order_id', $order->id)->where('attempt_number', 2)->firstOrFail();
        $this->assertSame('offered', $kedua->status);
        $this->assertNotEquals($pertama->mitra_id, $kedua->mitra_id); // mitra yang sama tidak ditawari lagi
        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
    }

    public function test_batas_percobaan_tercapai_membatalkan_order_walau_masih_ada_mitra_lain(): void
    {
        config(['marketplace.max_mitra_attempts' => 1]); // D-05 (default 3), dipersempit supaya tes ringkas

        $this->makeMitra('081300000011');
        $this->makeMitra('081300000012'); // masih ada kandidat, tapi batas percobaan sudah habis
        $order = $this->makeOrder();

        app(MatchingService::class)->advance($order);
        $this->assertSame(1, OrderMitraAttempt::where('order_id', $order->id)->count());

        $this->lewatiBatasRespons();
        $this->artisan('orders:process-expired-offers')->assertExitCode(0);

        $order = $order->fresh();
        $this->assertSame(OrderStatus::CANCELLED, $order->order_status);
        $this->assertSame('system', $order->cancelled_by);
        $this->assertNotNull($order->cancelled_at);

        // Tidak ada attempt #2
        $this->assertSame(1, OrderMitraAttempt::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'searching_mitra',
            'new_status' => 'cancelled',
            'note' => 'Tidak ada mitra yang menerima pesanan.',
        ]);
    }
}