<?php

namespace Tests\Feature\Order;

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ServiceAddon;
use App\Models\User;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes catch-up B-13 untuk P2a: /slots dan POST /orders.
// Semua waktu dipatok ke "besok" (bukan tanggal literal) supaya tes tidak patah di hari lain.
class SlotAndCreateOrderTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // kembalikan waktu normal
        parent::tearDown();
    }

    private function tanggalBesok(): string
    {
        return Carbon::tomorrow(config('app.timezone'))->format('Y-m-d');
    }

    private function urlSlots(string $tanggal): string
    {
        return "/api/slots?service_id={$this->service->id}&date={$tanggal}";
    }

    private function idAddon(string $nama): int
    {
        return ServiceAddon::where('addon_name', $nama)->firstOrFail()->id;
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'service_id' => $this->service->id,
            'property_size_id' => $this->size->id,
            'addons' => [['service_addon_id' => $this->idAddon('Pembersihan Sofa'), 'quantity' => 1]],
            'scheduled_at' => $this->jadwalBesok()->toIso8601String(), // besok 10:00
            'customer_name' => 'Budi Guest',
            'customer_phone' => '081355500001',
            'service_address' => 'Jl. Contoh No. 1, Bekasi',
        ], $override);
    }

    // ---------- /slots ----------

    public function test_slots_mengembalikan_enam_slot_dua_jam_yang_tersedia(): void
    {
        $this->makeMitra('081300000011');

        $res = $this->getJson($this->urlSlots($this->tanggalBesok()))
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure(['data' => [['start', 'end', 'available']]]);

        $slots = $res->json('data');
        foreach ($slots as $slot) {
            $this->assertTrue($slot['available']);
        }

        // Dibandingkan sebagai waktu (instan), bukan string, supaya tidak tergantung format offset
        $besok = Carbon::tomorrow(config('app.timezone'));
        $this->assertTrue(Carbon::parse($slots[0]['start'])->equalTo($besok->copy()->setTime(8, 0)));
        $this->assertTrue(Carbon::parse($slots[0]['end'])->equalTo($besok->copy()->setTime(10, 0)));
        $this->assertTrue(Carbon::parse($slots[5]['end'])->equalTo($besok->copy()->setTime(20, 0)));
    }

    public function test_slots_tidak_tersedia_tanpa_mitra_dan_validasi_input(): void
    {
        // Belum ada mitra sama sekali -> semua slot available = false
        $slots = $this->getJson($this->urlSlots($this->tanggalBesok()))->assertOk()->json('data');
        $this->assertCount(6, $slots);
        foreach ($slots as $slot) {
            $this->assertFalse($slot['available']);
        }

        // Jasa tidak ada -> 404
        $this->getJson('/api/slots?service_id=999999&date=' . $this->tanggalBesok())->assertStatus(404);

        // Format tanggal salah, tanggal kemarin, dan service_id kosong -> 422
        $this->getJson("/api/slots?service_id={$this->service->id}&date=12-11-2026")
            ->assertStatus(422)->assertJsonValidationErrors(['date']);
        $kemarin = Carbon::yesterday(config('app.timezone'))->format('Y-m-d');
        $this->getJson($this->urlSlots($kemarin))
            ->assertStatus(422)->assertJsonValidationErrors(['date']);
        $this->getJson('/api/slots?date=' . $this->tanggalBesok())
            ->assertStatus(422)->assertJsonValidationErrors(['service_id']);
    }

    public function test_slot_yang_terlalu_dekat_dengan_sekarang_tidak_tersedia(): void
    {
        $this->makeMitra('081300000011');
        $tanggal = $this->tanggalBesok();

        // "Sekarang" = besok 07:30. Slot 08:00 hanya 30 menit lagi (< MIN_LEAD_MINUTES 60), slot 10:00 aman.
        // Nilai dihitung SEBELUM setTestNow, karena setelahnya "besok" ikut bergeser.
        Carbon::setTestNow(Carbon::tomorrow(config('app.timezone'))->setTime(7, 30));

        $slots = $this->getJson($this->urlSlots($tanggal))->assertOk()->json('data');

        $this->assertFalse($slots[0]['available']);
        $this->assertTrue($slots[1]['available']);
    }

    public function test_slot_bentrok_dengan_order_mitra_penuh_tapi_slot_sebelah_tetap_tersedia(): void
    {
        $a = $this->makeMitra('081300000011');
        // Mitra A punya order terkonfirmasi besok 10:00-12:00
        $this->makeOrder($this->jadwalBesok(), OrderStatus::CONFIRMED, $a);

        $slots = $this->getJson($this->urlSlots($this->tanggalBesok()))->assertOk()->json('data');
        // Slot yang hanya bersentuhan (08-10 dan 12-14) bukan bentrok
        $this->assertSame([true, false, true, true, true, true], array_column($slots, 'available'));

        // Mitra kedua yang bebas membuat slot 10-12 tersedia lagi
        $this->makeMitra('081300000012');
        $slots = $this->getJson($this->urlSlots($this->tanggalBesok()))->json('data');
        $this->assertTrue($slots[1]['available']);
    }

    // ---------- POST /orders ----------

    public function test_guest_membuat_order_dengan_snapshot_riwayat_dan_penawaran_pertama(): void
    {
        $mitra = $this->makeMitra('081300000011');

        $res = $this->postJson('/api/orders', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('message', 'Pesanan berhasil dibuat. Sedang mencari mitra.')
            ->assertJsonPath('data.order_status', 'searching_mitra')
            ->assertJsonPath('data.tracking_stage', 'searching')
            ->assertJsonPath('data.customer.phone', '081355500001')
            ->assertJsonPath('data.addons.0.name', 'Pembersihan Sofa')
            ->assertJsonPath('data.addons.0.unit_price', 50000)
            ->assertJsonPath('data.pricing.total_price', 200000)
            ->assertJsonPath('data.mitra', null)
            ->assertJsonPath('data.payment', null);

        // D-10: JR- + 8 karakter acak tanpa 0, O, 1, I
        $kode = $res->json('data.order_code');
        $this->assertMatchesRegularExpression('/^JR-[A-HJ-NP-Z2-9]{8}$/', $kode);

        $order = Order::where('order_code', $kode)->firstOrFail();
        $this->assertNull($order->customer_id);   // guest
        $this->assertNull($order->mitra_id);      // belum ada mitra yang menerima
        $this->assertSame(200000, $order->total_price);
        $this->assertSame(120, $order->duration_minutes);
        $this->assertTrue($order->scheduled_at->equalTo($this->jadwalBesok()));

        // Snapshot add-on: nama dan harga disalin ke order_addons
        $this->assertDatabaseHas('order_addons', [
            'order_id' => $order->id,
            'addon_name' => 'Pembersihan Sofa',
            'unit_price' => 50000,
            'quantity' => 1,
            'subtotal' => 50000,
        ]);

        // Riwayat: (kosong) -> pending -> searching_mitra
        $riwayat = $order->statusHistories()->orderBy('id')->get()
            ->map(fn($h) => [$h->old_status, $h->new_status])->all();
        $this->assertSame([[null, 'pending'], ['pending', 'searching_mitra']], $riwayat);

        // Matching langsung menawarkan ke mitra pertama (attempt #1)
        $this->assertDatabaseHas('order_mitra_attempts', [
            'order_id' => $order->id,
            'mitra_id' => $mitra->id,
            'attempt_number' => 1,
            'status' => 'offered',
        ]);
    }

    public function test_customer_login_membuat_order_dengan_alamat_tersimpan_dan_role_lain_ditolak(): void
    {
        $mitra = $this->makeMitra('081300000011');

        $customer = User::create([
            'full_name' => 'Customer Login',
            'phone' => '081300000077',
            'password' => Hash::make('rahasia123'),
            'role' => 'customer',
            'account_status' => 'active',
        ]);
        $alamat = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_label' => 'Rumah',
            'recipient_name' => 'Customer Login',
            'recipient_phone' => '081300000077',
            'full_address' => 'Jl. Tersimpan No. 5, Bekasi',
            'is_primary' => true,
        ]);

        Sanctum::actingAs($customer, ['*']);

        $res = $this->postJson('/api/orders', $this->payload([
            'customer_phone' => '081355500002',
            'customer_address_id' => $alamat->id,
        ]))->assertStatus(201);

        $order = Order::where('order_code', $res->json('data.order_code'))->firstOrFail();
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals($alamat->id, $order->customer_address_id);

        // Alamat tersimpan milik customer LAIN ditolak
        $lain = User::create([
            'full_name' => 'Customer Lain',
            'phone' => '081300000078',
            'password' => Hash::make('rahasia123'),
            'role' => 'customer',
            'account_status' => 'active',
        ]);
        $alamatLain = CustomerAddress::create([
            'customer_id' => $lain->id,
            'address_label' => 'Rumah',
            'recipient_name' => 'Customer Lain',
            'recipient_phone' => '081300000078',
            'full_address' => 'Jl. Orang Lain No. 9',
            'is_primary' => true,
        ]);
        $this->postJson('/api/orders', $this->payload([
            'customer_phone' => '081355500003',
            'customer_address_id' => $alamatLain->id,
        ]))->assertStatus(422)->assertJsonPath('message', 'Alamat tersimpan tidak ditemukan.');

        // Mitra tidak boleh membuat pesanan
        $this->loginSebagai($mitra);
        $this->postJson('/api/orders', $this->payload(['customer_phone' => '081355500004']))
            ->assertStatus(403);

        $this->assertSame(1, Order::count());
    }

    public function test_total_harga_palsu_dari_client_diabaikan_server_menghitung_ulang(): void
    {
        $this->makeMitra('081300000011');
        $sofa = $this->idAddon('Pembersihan Sofa');

        // Dugaan bug #1: harga bertipe string lolos. Client mengirim harga palsu di banyak tempat.
        $res = $this->postJson('/api/orders', $this->payload([
            'total_price' => 1,
            'service_price' => '1',
            'addons_total' => '0',
            'addons' => [
                [
                    'service_addon_id' => $sofa,
                    'quantity' => '2',        // string, harus tetap diperlakukan sebagai angka
                    'unit_price' => '1',      // harga palsu per add-on
                ]
            ],
        ]))->assertStatus(201);

        // 150000 + (50000 x 2) = 250000, semuanya integer dari server
        $pricing = $res->json('data.pricing');
        $this->assertSame(150000, $pricing['service_price']);
        $this->assertSame(100000, $pricing['addons_total']);
        $this->assertSame(250000, $pricing['total_price']);
        $this->assertSame(50000, $res->json('data.addons.0.unit_price'));
        $this->assertSame(2, $res->json('data.addons.0.quantity'));

        $order = Order::where('order_code', $res->json('data.order_code'))->firstOrFail();
        $this->assertSame(250000, $order->total_price);
    }

    public function test_slot_tidak_valid_terlalu_dekat_atau_tanpa_mitra_ditolak_dan_tidak_menyimpan_order(): void
    {
        // Sengaja TIDAK ada mitra. Urutan cek di OrderService: slot -> jarak waktu -> mitra eligible.

        // (a) Jam bukan awal slot (10:30) -> 422
        $this->postJson('/api/orders', $this->payload([
            'scheduled_at' => $this->jadwalBesok()->setTime(10, 30)->toIso8601String(),
        ]))->assertStatus(422)->assertJsonPath('message', 'Waktu pesanan harus salah satu slot yang tersedia.');

        // (b) Slot valid tapi terlalu dekat dengan "sekarang" -> 422
        // Nilai dihitung SEBELUM setTestNow supaya "besok" tidak bergeser.
        $slotDekat = Carbon::tomorrow(config('app.timezone'))->setTime(8, 0)->toIso8601String();
        Carbon::setTestNow(Carbon::tomorrow(config('app.timezone'))->setTime(7, 30));

        $this->postJson('/api/orders', $this->payload(['scheduled_at' => $slotDekat]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Slot ini terlalu dekat dengan waktu sekarang. Pilih slot berikutnya.');

        Carbon::setTestNow();

        // (c) Slot valid dan cukup jauh, tapi tidak ada mitra eligible -> 409
        $this->postJson('/api/orders', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'Slot waktu ini sudah tidak tersedia. Pilih slot lain.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_input_tidak_valid_di_buat_order_ditolak_422_termasuk_quantity_negatif(): void
    {
        $sofa = $this->idAddon('Pembersihan Sofa');

        // Dugaan bug #2 versi buat order
        foreach ([0, -1, 21, 'abc'] as $qty) {
            $this->postJson('/api/orders', $this->payload([
                'addons' => [['service_addon_id' => $sofa, 'quantity' => $qty]],
            ]))->assertStatus(422)->assertJsonValidationErrors(['addons.0.quantity']);
        }

        $this->postJson('/api/orders', $this->payload(['customer_phone' => '12345']))
            ->assertStatus(422)->assertJsonValidationErrors(['customer_phone']);

        $this->postJson('/api/orders', $this->payload(['scheduled_at' => 'bukan tanggal']))
            ->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);

        $this->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_id', 'property_size_id', 'scheduled_at', 'customer_name', 'customer_phone', 'service_address']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_dobel_tap_pesanan_identik_ditolak_409_tapi_slot_lain_boleh(): void
    {
        $this->makeMitra('081300000011');

        $this->postJson('/api/orders', $this->payload())->assertStatus(201);

        // Request yang persis sama (dobel-tap) -> 409, tidak membuat order kedua
        $this->postJson('/api/orders', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'Pesanan yang sama sudah dibuat. Cek lewat menu Lacak Pesanan.');
        $this->assertSame(1, Order::count());

        // Customer yang sama, jasa sama, tapi slot BERBEDA (12:00) = pesanan baru yang sah
        $this->postJson('/api/orders', $this->payload([
            'scheduled_at' => $this->jadwalBesok()->setTime(12, 0)->toIso8601String(),
        ]))->assertStatus(201);
        $this->assertSame(2, Order::count());
    }
}