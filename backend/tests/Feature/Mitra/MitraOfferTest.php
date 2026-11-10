<?php

namespace Tests\Feature\Mitra;

use App\Models\MitraAvailability;
use App\Models\MitraProfile;
use App\Models\Order;
use App\Models\OrderMitraAttempt;
use App\Models\PropertySize;
use App\Models\Service;
use App\Models\User;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Tes P3b (B-13). Data dibuat langsung lewat model supaya mitra yang ditawari bisa dikontrol
// (matching asli memilih mitra secara acak).
//
// CATATAN JUJUR: PHPUnit berjalan di satu proses, jadi request datang berurutan. Tes ini membuktikan
// RE-CHECK setelah kunci (request kedua melihat hasil request pertama). Bukti bahwa lockForUpdate
// benar-benar menahan race butuh dua proses sungguhan (dev:race-accept, menyusul setelah P3c).
class MitraOfferTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;
    private PropertySize $size;
    private int $urut = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);

        $this->service = Service::where('service_name', 'Home Cleaning')->firstOrFail();
        $this->size = PropertySize::where('size_name', 'Rumah Sedang')->firstOrFail();

        // 3 user pengisi supaya users.id TIDAK sama dengan mitra_profiles.id (jebakan B-11)
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'full_name' => "Pengisi $i",
                'phone' => '08139000000' . $i,
                'password' => Hash::make('rahasia123'),
                'role' => 'customer',
                'account_status' => 'active',
            ]);
        }
    }

    // ---------- helper ----------

    private function makeMitra(string $phone, string $verification = 'approved'): MitraProfile
    {
        $user = User::create([
            'full_name' => 'Mitra ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('rahasia123'),
            'role' => 'mitra',
            'account_status' => 'active',
        ]);

        $profile = MitraProfile::create([
            'user_id' => $user->id,
            'verification_status' => $verification,
            'is_available' => true,
        ]);
        $profile->setRelation('user', $user);

        $profile->services()->attach($this->service->id, ['status' => 'active']);

        // Jadwal tiap hari 08-20, supaya tes tidak bergantung hari apa tes dijalankan
        for ($hari = 0; $hari <= 6; $hari++) {
            MitraAvailability::create([
                'mitra_id' => $profile->id,
                'day_of_week' => $hari,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_active' => true,
            ]);
        }

        return $profile;
    }

    private function jadwalBesok(): Carbon
    {
        return Carbon::tomorrow(config('app.timezone'))->setTime(10, 0);
    }

    private function makeOrder(?Carbon $jadwal = null, string $status = OrderStatus::SEARCHING_MITRA): Order
    {
        $this->urut++;

        return Order::create([
            'order_code' => 'JR-TEST' . str_pad((string) $this->urut, 3, '0', STR_PAD_LEFT),
            'customer_id' => null,
            'mitra_id' => null,
            'service_id' => $this->service->id,
            'property_size_id' => $this->size->id,
            'scheduled_at' => $jadwal ?? $this->jadwalBesok(),
            'duration_minutes' => 120,
            'customer_name' => 'Budi Tes',
            'customer_phone' => '081311111111',
            'service_address' => 'Jl. Rahasia No. 99, Bekasi',
            'service_price' => 150000,
            'addons_total' => 0,
            'total_price' => 150000,
            'order_status' => $status,
        ]);
    }

    // $umurDetik = sudah berapa detik tawaran ini dibuat
    private function makeOffer(Order $order, MitraProfile $mitra, int $nomor = 1, int $umurDetik = 0): OrderMitraAttempt
    {
        return OrderMitraAttempt::create([
            'order_id' => $order->id,
            'mitra_id' => $mitra->id,
            'attempt_number' => $nomor,
            'status' => 'offered',
            'offered_at' => now()->subSeconds($umurDetik),
            'responded_at' => null,
        ]);
    }

    private function loginSebagai(MitraProfile $mitra): void
    {
        Sanctum::actingAs($mitra->user, ['*']);
    }

    // ---------- tes ----------

    public function test_accept_berhasil_menetapkan_mitra_dan_status_menunggu_bayar(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.order_status', 'waiting_payment')
            ->assertJsonPath('data.tracking_stage', 'waiting')
            ->assertJsonPath('data.mitra.mitra_id', $a->id)
            ->assertJsonPath('data.customer.phone', '081311111111') // terbuka setelah accept (D-12)
            ->assertJsonPath('data.service_address', 'Jl. Rahasia No. 99, Bekasi');

        // mitra_id = mitra_profiles.id, BUKAN users.id (B-11)
        $this->assertNotSame($a->id, $a->user_id);
        $this->assertSame($a->id, $order->fresh()->mitra_id);
        $this->assertSame('accepted', $attempt->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'new_status' => 'waiting_payment',
            'changed_by_user_id' => $a->user_id,
        ]);
    }

    public function test_daftar_offer_tidak_membocorkan_telepon_dan_alamat(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($a);
        $res = $this->getJson('/api/mitra/offers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attempt_id', $attempt->id)
            ->assertJsonPath('data.0.service.service_name', 'Home Cleaning')
            ->assertJsonPath('data.0.pricing.total_price', 150000)
            ->assertJsonStructure(['data' => [['attempt_id', 'expires_at', 'scheduled_at', 'duration_minutes', 'addons']]]);

        $body = $res->getContent();
        $this->assertStringNotContainsString('081311111111', $body);
        $this->assertStringNotContainsString('Jl. Rahasia', $body);
        $this->assertStringNotContainsString($order->order_code, $body);
    }

    public function test_accept_dobel_pada_attempt_yang_sama_ditolak_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")->assertOk();
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")->assertStatus(409);

        // Transisi ke waiting_payment hanya tercatat sekali
        $this->assertSame(1, $order->statusHistories()->where('new_status', 'waiting_payment')->count());
    }

    public function test_accept_tawaran_kedaluwarsa_ditolak_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder();
        $umur = (int) config('marketplace.mitra_response_seconds') + 10;
        $attempt = $this->makeOffer($order, $a, 1, $umur);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")->assertStatus(409);

        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
        $this->assertNull($order->fresh()->mitra_id);
    }

    public function test_accept_order_yang_sudah_tidak_mencari_mitra_ditolak_409(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder(null, OrderStatus::CANCELLED);
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")->assertStatus(409);

        $this->assertNull($order->fresh()->mitra_id);
    }

    public function test_accept_basi_mitra_kedua_setelah_order_diambil_mitra_pertama_409(): void
    {
        // Mensimulasikan hasil race: dua tawaran 'offered' pada order yang sama.
        // (Di sistem normal matching sekuensial, tapi kondisi ini bisa muncul saat race.)
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $order = $this->makeOrder();
        $attemptA = $this->makeOffer($order, $a, 1);
        $attemptB = $this->makeOffer($order, $b, 2);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attemptA->id}/accept")->assertOk();

        $this->loginSebagai($b);
        $this->postJson("/api/mitra/offers/{$attemptB->id}/accept")->assertStatus(409);

        // Pemenangnya tetap A
        $this->assertSame($a->id, $order->fresh()->mitra_id);
        $this->assertSame('offered', $attemptB->fresh()->status);
    }

    public function test_accept_dua_order_berjam_sama_oleh_mitra_yang_sama_yang_kedua_409(): void
    {
        $m = $this->makeMitra('081300000011');
        $order1 = $this->makeOrder();
        $order2 = $this->makeOrder(); // slot sama
        $attempt1 = $this->makeOffer($order1, $m);
        $attempt2 = $this->makeOffer($order2, $m);

        $this->loginSebagai($m);
        $this->postJson("/api/mitra/offers/{$attempt1->id}/accept")->assertOk();
        $this->postJson("/api/mitra/offers/{$attempt2->id}/accept")->assertStatus(409);

        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order2->fresh()->order_status);
        $this->assertNull($order2->fresh()->mitra_id);
    }

    public function test_mitra_lain_tidak_boleh_menyentuh_tawaran_bukan_miliknya_404(): void
    {
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($b);
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")->assertStatus(404);
        $this->postJson("/api/mitra/offers/{$attempt->id}/reject")->assertStatus(404);

        $this->assertSame('offered', $attempt->fresh()->status);
        $this->assertNull($order->fresh()->mitra_id);
    }

    public function test_mitra_pending_tidak_boleh_accept_403(): void
    {
        $p = $this->makeMitra('081300000011', 'pending');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $p);

        $this->loginSebagai($p);
        $this->postJson("/api/mitra/offers/{$attempt->id}/accept")->assertStatus(403);

        $this->assertNull($order->fresh()->mitra_id);
    }

    public function test_reject_menawarkan_ke_mitra_berikutnya(): void
    {
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attempt->id}/reject")->assertOk();

        $this->assertSame('rejected', $attempt->fresh()->status);
        $this->assertSame(OrderStatus::SEARCHING_MITRA, $order->fresh()->order_status);
        $this->assertDatabaseHas('order_mitra_attempts', [
            'order_id' => $order->id,
            'mitra_id' => $b->id,
            'attempt_number' => 2,
            'status' => 'offered',
        ]);
    }

    public function test_reject_tanpa_kandidat_lain_membatalkan_order(): void
    {
        $a = $this->makeMitra('081300000011');
        $order = $this->makeOrder();
        $attempt = $this->makeOffer($order, $a);

        $this->loginSebagai($a);
        $this->postJson("/api/mitra/offers/{$attempt->id}/reject")->assertOk();

        $order = $order->fresh();
        $this->assertSame(OrderStatus::CANCELLED, $order->order_status);
        $this->assertSame('system', $order->cancelled_by);
    }

    public function test_daftar_offer_menutup_tawaran_kedaluwarsa_dan_oper_ke_mitra_lain(): void
    {
        // B-15: kedaluwarsa diproses lazy saat endpoint baca dipanggil, tanpa scheduler
        $a = $this->makeMitra('081300000011');
        $b = $this->makeMitra('081300000012');
        $order = $this->makeOrder();
        $umur = (int) config('marketplace.mitra_response_seconds') + 10;
        $attempt = $this->makeOffer($order, $a, 1, $umur);

        $this->loginSebagai($a);
        $this->getJson('/api/mitra/offers')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertSame('expired', $attempt->fresh()->status);
        $this->assertDatabaseHas('order_mitra_attempts', [
            'order_id' => $order->id,
            'mitra_id' => $b->id,
            'status' => 'offered',
        ]);
    }
}