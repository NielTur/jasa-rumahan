<?php

namespace Tests\Feature\Catalog;

use App\Models\PropertySize;
use App\Models\PropertyType;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Tes catch-up B-13 untuk P1: katalog publik dan /orders/quote (harga dihitung server, B-3).
class CatalogQuoteTest extends TestCase
{
    use RefreshDatabase;

    private Service $homeCleaning;
    private PropertySize $rumahSedang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);

        $this->homeCleaning = Service::where('service_name', 'Home Cleaning')->firstOrFail();
        $this->rumahSedang = PropertySize::where('size_name', 'Rumah Sedang')->firstOrFail();
    }

    private function addon(string $nama): ServiceAddon
    {
        return ServiceAddon::where('addon_name', $nama)->firstOrFail();
    }

    private function body(array $override = []): array
    {
        return array_merge([
            'service_id' => $this->homeCleaning->id,
            'property_size_id' => $this->rumahSedang->id,
        ], $override);
    }

    private function quote(array $body)
    {
        return $this->postJson('/api/orders/quote', $body);
    }

    public function test_kategori_dan_jasa_hanya_yang_aktif_dan_bisa_difilter(): void
    {
        $this->getJson('/api/service-categories')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'category_name', 'description']]]);

        $daftar = $this->getJson('/api/services')->assertOk()->assertJsonCount(3, 'data');
        // Daftar ringkas: tanpa addons dan prices
        $this->assertArrayNotHasKey('prices', $daftar->json('data.0'));
        $this->assertArrayNotHasKey('addons', $daftar->json('data.0'));

        ServiceCategory::where('category_name', 'Perawatan Pohon')->update(['is_active' => false]);
        Service::where('service_name', 'Lawn Mowing')->update(['is_active' => false]);

        $this->getJson('/api/service-categories')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.service_name', 'Home Cleaning');

        $katBersih = ServiceCategory::where('category_name', 'Kebersihan Rumah')->value('id');
        $katTaman = ServiceCategory::where('category_name', 'Perawatan Taman')->value('id');
        $this->getJson("/api/services?category_id={$katBersih}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/services?category_id={$katTaman}")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_detail_jasa_memuat_addon_dan_harga_terkelompok_per_jenis_properti(): void
    {
        $res = $this->getJson("/api/services/{$this->homeCleaning->id}")
            ->assertOk()
            ->assertJsonPath('data.service_name', 'Home Cleaning')
            ->assertJsonPath('data.duration_minutes', 120)
            ->assertJsonCount(3, 'data.addons')
            ->assertJsonCount(2, 'data.prices')
            ->assertJsonPath('data.prices.0.type_name', 'Rumah')
            ->assertJsonCount(3, 'data.prices.0.sizes')
            ->assertJsonPath('data.prices.1.type_name', 'Apartemen')
            ->assertJsonCount(2, 'data.prices.1.sizes');

        // Uang di API = integer rupiah (D-17), bukan string/float
        $sedang = collect($res->json('data.prices.0.sizes'))->firstWhere('size_name', 'Rumah Sedang');
        $this->assertSame(150000, $sedang['price']);
        $this->assertIsInt($res->json('data.addons.0.price'));

        // Add-on dan harga nonaktif tidak tampil
        $this->addon('Cuci Karpet')->update(['is_active' => false]);
        ServicePrice::where('service_id', $this->homeCleaning->id)
            ->where('property_size_id', $this->rumahSedang->id)
            ->update(['is_active' => false]);

        $this->getJson("/api/services/{$this->homeCleaning->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.addons')
            ->assertJsonCount(2, 'data.prices.0.sizes');
    }

    public function test_detail_jasa_nonaktif_atau_tidak_ada_404(): void
    {
        $lawn = Service::where('service_name', 'Lawn Mowing')->firstOrFail();
        $lawn->update(['is_active' => false]);

        $this->getJson("/api/services/{$lawn->id}")->assertStatus(404);
        $this->getJson('/api/services/999999')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Jasa tidak ditemukan.');
        $this->getJson('/api/services/abc')->assertStatus(404); // whereNumber: rute tidak cocok
    }

    public function test_jenis_properti_hanya_yang_aktif_beserta_ukurannya(): void
    {
        $this->getJson('/api/property-types')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(3, 'data.0.sizes')
            ->assertJsonCount(2, 'data.1.sizes');

        PropertySize::where('size_name', 'Apartemen Studio')->update(['is_active' => false]);
        $this->getJson('/api/property-types')->assertOk()->assertJsonCount(1, 'data.1.sizes');

        PropertyType::where('type_name', 'Apartemen')->update(['is_active' => false]);
        $this->getJson('/api/property-types')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_quote_menghitung_harga_di_server_dan_mengabaikan_total_dari_client(): void
    {
        $sofa = $this->addon('Pembersihan Sofa');
        $kulkas = $this->addon('Pembersihan Kulkas');

        $res = $this->quote($this->body([
            'addons' => [
                ['service_addon_id' => $sofa->id, 'quantity' => 1],
                ['service_addon_id' => $kulkas->id, 'quantity' => '2'], // quantity bertipe string tetap diterima
            ],
            // Angka palsu dari client harus diabaikan (B-3)
            'total_price' => 1,
            'service_price' => '1',
        ]))
            ->assertOk()
            ->assertJsonPath('data.service_name', 'Home Cleaning')
            ->assertJsonPath('data.size_name', 'Rumah Sedang')
            ->assertJsonPath('data.duration_minutes', 120)
            ->assertJsonCount(2, 'data.addons');

        // 150000 + 50000 + (35000 x 2) = 270000, semuanya integer
        $pricing = $res->json('data.pricing');
        $this->assertSame(150000, $pricing['service_price']);
        $this->assertSame(120000, $pricing['addons_total']);
        $this->assertSame(270000, $pricing['total_price']);

        $barisKulkas = collect($res->json('data.addons'))->firstWhere('name', 'Pembersihan Kulkas');
        $this->assertSame(35000, $barisKulkas['unit_price']);
        $this->assertSame(2, $barisKulkas['quantity']);
        $this->assertSame(70000, $barisKulkas['subtotal']);

        // Tanpa add-on: boleh dihilangkan
        $polos = $this->quote($this->body())->assertOk();
        $this->assertSame(150000, $polos->json('data.pricing.total_price'));
        $this->assertSame(0, $polos->json('data.pricing.addons_total'));
        $this->assertSame([], $polos->json('data.addons'));
    }

    public function test_quote_menolak_kombinasi_tidak_valid_dengan_422(): void
    {
        $lawn = Service::where('service_name', 'Lawn Mowing')->firstOrFail();
        $aptStudio = PropertySize::where('size_name', 'Apartemen Studio')->firstOrFail();

        // Jasa + ukuran yang tidak punya harga (Lawn Mowing tidak melayani apartemen)
        $this->quote(['service_id' => $lawn->id, 'property_size_id' => $aptStudio->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Jasa ini belum tersedia untuk ukuran properti tersebut.');

        // Add-on milik jasa lain
        $rumput = $this->addon('Buang Sampah Rumput');
        $this->quote($this->body(['addons' => [['service_addon_id' => $rumput->id]]]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ada add-on yang tidak valid untuk jasa ini.');

        // Add-on yang tidak ada
        $this->quote($this->body(['addons' => [['service_addon_id' => 999999]]]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ada add-on yang tidak valid untuk jasa ini.');

        // Jasa nonaktif
        $lawn->update(['is_active' => false]);
        $this->quote(['service_id' => $lawn->id, 'property_size_id' => $this->rumahSedang->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Jasa tidak ditemukan atau sedang tidak aktif.');

        // Ukuran nonaktif
        $besar = PropertySize::where('size_name', 'Rumah Besar')->firstOrFail();
        $besar->update(['is_active' => false]);
        $this->quote($this->body(['property_size_id' => $besar->id]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ukuran properti tidak ditemukan atau sedang tidak aktif.');
    }

    public function test_quote_menolak_quantity_nol_negatif_dan_lebih_dari_20(): void
    {
        $sofa = $this->addon('Pembersihan Sofa');

        // Dugaan bug #2: quantity negatif belum ditolak. Tes ini membuktikan benar atau tidak.
        foreach ([0, -1, 21, 'abc'] as $qty) {
            $this->quote($this->body(['addons' => [['service_addon_id' => $sofa->id, 'quantity' => $qty]]]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['addons.0.quantity']);
        }

        // Batas atas tepat 20 masih boleh: 50000 x 20 = 1.000.000
        $res = $this->quote($this->body(['addons' => [['service_addon_id' => $sofa->id, 'quantity' => 20]]]))
            ->assertOk();
        $this->assertSame(1000000, $res->json('data.pricing.addons_total'));

        // Add-on dobel dalam satu request ditolak
        $this->quote($this->body([
            'addons' => [
                ['service_addon_id' => $sofa->id],
                ['service_addon_id' => $sofa->id],
            ]
        ]))->assertStatus(422)->assertJsonValidationErrors(['addons.0.service_addon_id']);

        // Field wajib
        $this->quote(['service_id' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_id', 'property_size_id']);
    }

    public function test_ubah_harga_di_database_membuat_quote_ikut_berubah(): void
    {
        $sofa = $this->addon('Pembersihan Sofa');
        $body = $this->body(['addons' => [['service_addon_id' => $sofa->id, 'quantity' => 1]]]);

        $this->assertSame(200000, $this->quote($body)->assertOk()->json('data.pricing.total_price'));

        // Seperti admin mengubah harga lewat dashboard: tanpa ubah kode (A-4)
        ServicePrice::where('service_id', $this->homeCleaning->id)
            ->where('property_size_id', $this->rumahSedang->id)
            ->update(['price' => 175000]);
        $sofa->update(['price' => 55000]);

        $this->assertSame(230000, $this->quote($body)->assertOk()->json('data.pricing.total_price'));

        // Harga dinonaktifkan -> kombinasi tidak bisa dipesan lagi
        ServicePrice::where('service_id', $this->homeCleaning->id)
            ->where('property_size_id', $this->rumahSedang->id)
            ->update(['is_active' => false]);

        $this->quote($body)->assertStatus(422);
    }
}