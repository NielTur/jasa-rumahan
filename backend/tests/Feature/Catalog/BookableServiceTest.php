<?php

namespace Tests\Feature\Catalog;

use App\Models\ServiceCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

// Tes CR-16: jasa di kategori nonaktif tidak boleh tampil maupun dipesan,
// di semua pintu masuk (katalog, quote, slots, buat order, pilihan jasa mitra).
class BookableServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
    }

    // Home Cleaning ada di kategori "Kebersihan Rumah"; dua jasa lain di kategori berbeda
    private function matikanKategoriHomeCleaning(): void
    {
        ServiceCategory::whereKey($this->service->category_id)->update(['is_active' => false]);
    }

    public function test_jasa_di_kategori_nonaktif_hilang_dari_daftar_dan_detailnya_404(): void
    {
        $this->getJson("/api/services/{$this->service->id}")->assertOk();

        $this->matikanKategoriHomeCleaning();

        $this->getJson("/api/services/{$this->service->id}")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Jasa tidak ditemukan.');

        $ids = collect($this->getJson('/api/services')->assertOk()->json('data'))->pluck('id');
        $this->assertCount(2, $ids); // Lawn Mowing dan Tree Trimming tetap tampil
        $this->assertFalse($ids->contains($this->service->id));
    }

    public function test_quote_slots_dan_buat_order_menolak_jasa_di_kategori_nonaktif(): void
    {
        $this->makeMitra('081300000011');
        $tanggal = Carbon::tomorrow(config('app.timezone'))->format('Y-m-d');
        $urlSlots = "/api/slots?service_id={$this->service->id}&date={$tanggal}";
        $quote = ['service_id' => $this->service->id, 'property_size_id' => $this->size->id];
        $order = $quote + [
            'scheduled_at' => $this->jadwalBesok()->toIso8601String(),
            'customer_name' => 'Budi Guest',
            'customer_phone' => '081355500001',
            'service_address' => 'Jl. Contoh No. 1, Bekasi',
        ];

        // Kontrol: sebelum kategori dimatikan, quote dan slot jalan normal
        $this->postJson('/api/orders/quote', $quote)->assertOk();
        $this->assertTrue($this->getJson($urlSlots)->assertOk()->json('data.1.available'));

        $this->matikanKategoriHomeCleaning();

        $this->postJson('/api/orders/quote', $quote)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Jasa tidak ditemukan atau sedang tidak aktif.');

        $this->getJson($urlSlots)->assertStatus(404);

        $this->postJson('/api/orders', $order)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Jasa tidak ditemukan atau sedang tidak aktif.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_mitra_tidak_melihat_dan_tidak_bisa_memilih_jasa_di_kategori_nonaktif(): void
    {
        $mitra = $this->makeMitra('081300000011'); // sudah menyediakan Home Cleaning
        $this->loginSebagai($mitra);

        $this->matikanKategoriHomeCleaning();

        $ids = collect($this->getJson('/api/mitra/services')->assertOk()->json('data'))->pluck('id');
        $this->assertCount(2, $ids);
        $this->assertFalse($ids->contains($this->service->id));

        $this->putJson('/api/mitra/services', ['service_ids' => [$this->service->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_ids.0']);
    }
}