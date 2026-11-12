<?php

namespace Tests\Feature\Admin;

use App\Models\PropertySize;
use App\Models\PropertyType;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tes T3b (B-13, A-3, A-4): CRUD data master lewat Admin Dashboard, dan dampaknya ke API publik.
class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);

        $this->admin = User::create([
            'full_name' => 'Admin Tes',
            'phone' => '081200000001',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);
    }

    private function masuk(): void
    {
        $this->actingAs($this->admin);
    }

    // Halaman daftar => teks yang pasti ada dari data seeder
    private function halaman(): array
    {
        return [
            '/admin/service-categories' => 'Kebersihan Rumah',
            '/admin/services' => 'Home Cleaning',
            '/admin/property-types' => 'Apartemen',
            '/admin/property-sizes' => 'Rumah Sedang',
            '/admin/service-prices' => 'Rp 150.000',
            '/admin/service-addons' => 'Pembersihan Sofa',
        ];
    }

    private function jasa(string $nama): Service
    {
        return Service::where('service_name', $nama)->firstOrFail();
    }

    private function ukuran(string $nama): PropertySize
    {
        return PropertySize::where('size_name', $nama)->firstOrFail();
    }

    public function test_hanya_admin_yang_bisa_membuka_halaman_master(): void
    {
        foreach (array_keys($this->halaman()) as $url) {
            $this->get($url)->assertRedirect(route('admin.login')); // tamu
        }

        $customer = User::create([
            'full_name' => 'Customer Tes',
            'phone' => '081200000011',
            'password' => Hash::make('rahasia123'),
            'role' => 'customer',
            'account_status' => 'active',
        ]);

        foreach (array_keys($this->halaman()) as $url) {
            $this->actingAs($customer)->get($url)->assertStatus(403);
        }

        $this->actingAs($customer)
            ->post('/admin/service-categories', ['category_name' => 'Kategori Gelap'])
            ->assertStatus(403);
        $this->assertDatabaseMissing('service_categories', ['category_name' => 'Kategori Gelap']);
    }

    public function test_semua_halaman_daftar_menampilkan_data_seed_dan_menu_muncul(): void
    {
        $this->masuk();

        foreach ($this->halaman() as $url => $teks) {
            $this->get($url)->assertOk()->assertSee($teks);
        }

        // Item menu muncul otomatis karena rutenya sudah ada
        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Kategori Jasa')
            ->assertSee('Harga Jasa')
            ->assertSee('Add-on');
    }

    public function test_admin_membuat_kategori_dan_validasi_gagal_tidak_menyimpan(): void
    {
        $this->masuk();

        $this->get('/admin/service-categories/create')->assertOk()->assertSee('Nama kategori');

        $this->post('/admin/service-categories', [
            'category_name' => 'Perawatan Kolam',
            'description' => 'Kuras dan bersihkan kolam.',
        ])->assertRedirect('/admin/service-categories');

        $this->assertDatabaseHas('service_categories', [
            'category_name' => 'Perawatan Kolam',
            'is_active' => 1, // data baru langsung aktif
        ]);

        // Nama kosong dan nama dobel ditolak
        $this->from('/admin/service-categories/create')
            ->post('/admin/service-categories', ['category_name' => ''])
            ->assertRedirect('/admin/service-categories/create')
            ->assertSessionHasErrors('category_name');

        $this->from('/admin/service-categories/create')
            ->post('/admin/service-categories', ['category_name' => 'Perawatan Kolam'])
            ->assertSessionHasErrors('category_name');

        $this->assertSame(4, ServiceCategory::count()); // 3 seed + 1 baru
    }

    public function test_admin_mengubah_dan_menonaktifkan_kategori_tanpa_bisa_menghapus(): void
    {
        $this->masuk();
        $kat = ServiceCategory::where('category_name', 'Perawatan Pohon')->firstOrFail();

        $this->get("/admin/service-categories/{$kat->id}/edit")->assertOk()->assertSee('Perawatan Pohon');

        $this->put("/admin/service-categories/{$kat->id}", [
            'category_name' => 'Perawatan Pohon dan Tanaman',
            'description' => null,
        ])->assertRedirect('/admin/service-categories');
        $this->assertSame('Perawatan Pohon dan Tanaman', $kat->fresh()->category_name);

        // Nonaktifkan lalu aktifkan lagi
        $this->patch("/admin/service-categories/{$kat->id}/toggle")->assertRedirect('/admin/service-categories');
        $this->assertFalse((bool) $kat->fresh()->is_active);
        $this->patch("/admin/service-categories/{$kat->id}/toggle")->assertRedirect('/admin/service-categories');
        $this->assertTrue((bool) $kat->fresh()->is_active);

        // Tidak ada rute hapus (B-12): DELETE dijawab 405 dan datanya utuh
        $this->delete("/admin/service-categories/{$kat->id}")->assertStatus(405);
        $this->assertDatabaseHas('service_categories', ['id' => $kat->id]);
    }

    public function test_menonaktifkan_kategori_dari_admin_menutup_jasanya_di_api_katalog(): void
    {
        $home = $this->jasa('Home Cleaning');
        $this->getJson("/api/services/{$home->id}")->assertOk();

        $this->masuk();
        $this->patch("/admin/service-categories/{$home->category_id}/toggle");

        // CR-16: kategori mati -> jasanya tidak bisa tampil maupun dipesan
        $this->getJson("/api/services/{$home->id}")->assertStatus(404);

        $this->patch("/admin/service-categories/{$home->category_id}/toggle");
        $this->getJson("/api/services/{$home->id}")->assertOk();
    }

    public function test_admin_membuat_dan_mengubah_jasa_dengan_validasi(): void
    {
        $this->masuk();
        $taman = ServiceCategory::where('category_name', 'Perawatan Taman')->firstOrFail();

        $this->post('/admin/services', [
            'category_id' => $taman->id,
            'service_name' => 'Pembersihan Kolam',
            'description' => 'Kuras kolam.',
            'duration_minutes' => 90,
        ])->assertRedirect('/admin/services');

        $this->assertDatabaseHas('services', [
            'service_name' => 'Pembersihan Kolam',
            'category_id' => $taman->id,
            'duration_minutes' => 90,
            'is_active' => 1,
        ]);

        // Kategori tidak ada dan durasi di luar batas ditolak
        $this->from('/admin/services/create')
            ->post('/admin/services', ['category_id' => 999999, 'service_name' => 'Jasa X'])
            ->assertSessionHasErrors('category_id');
        $this->from('/admin/services/create')
            ->post('/admin/services', ['category_id' => $taman->id, 'service_name' => 'Jasa Y', 'duration_minutes' => 5])
            ->assertSessionHasErrors('duration_minutes');
        $this->assertDatabaseMissing('services', ['service_name' => 'Jasa X']);
        $this->assertDatabaseMissing('services', ['service_name' => 'Jasa Y']);

        // Ubah nama dan kosongkan durasi
        $kolam = $this->jasa('Pembersihan Kolam');
        $this->put("/admin/services/{$kolam->id}", [
            'category_id' => $taman->id,
            'service_name' => 'Kuras Kolam',
            'duration_minutes' => null,
        ])->assertRedirect('/admin/services');

        $kolam = $kolam->fresh();
        $this->assertSame('Kuras Kolam', $kolam->service_name);
        $this->assertNull($kolam->duration_minutes);
        $this->get("/admin/services/{$kolam->id}/edit")->assertOk()->assertSee('Kuras Kolam');
    }

    public function test_ubah_harga_lewat_form_langsung_dipakai_quote_tanpa_ubah_kode(): void
    {
        $home = $this->jasa('Home Cleaning');
        $sedang = $this->ukuran('Rumah Sedang');
        $body = ['service_id' => $home->id, 'property_size_id' => $sedang->id];

        $this->postJson('/api/orders/quote', $body)
            ->assertOk()
            ->assertJsonPath('data.pricing.total_price', 150000);

        $this->masuk();
        $harga = ServicePrice::where('service_id', $home->id)->where('property_size_id', $sedang->id)->firstOrFail();
        $editUrl = "/admin/service-prices/{$harga->id}/edit";

        $this->get($editUrl)->assertOk()->assertSee('150000');

        $this->put("/admin/service-prices/{$harga->id}", [
            'service_id' => $home->id,
            'property_size_id' => $sedang->id,
            'price' => 175000,
        ])->assertRedirect('/admin/service-prices');

        // A-4: harga baru langsung dipakai quote
        $total = $this->postJson('/api/orders/quote', $body)->assertOk()->json('data.pricing.total_price');
        $this->assertSame(175000, $total);

        // Harga tidak valid ditolak dan tidak mengubah apa pun
        foreach ([0, 'abc', 200000000] as $salah) {
            $this->from($editUrl)
                ->put("/admin/service-prices/{$harga->id}", [
                    'service_id' => $home->id,
                    'property_size_id' => $sedang->id,
                    'price' => $salah,
                ])
                ->assertRedirect($editUrl)
                ->assertSessionHasErrors('price');
        }
        $this->assertSame(175000, $harga->fresh()->price);
    }

    public function test_harga_dobel_untuk_kombinasi_sama_ditolak_dan_harga_baru_membuka_kombinasi(): void
    {
        $this->masuk();
        $home = $this->jasa('Home Cleaning');
        $sedang = $this->ukuran('Rumah Sedang');
        $lawn = $this->jasa('Lawn Mowing');
        $studio = $this->ukuran('Apartemen Studio');

        // Kombinasi yang sudah punya harga tidak boleh dibuat lagi
        $this->from('/admin/service-prices/create')
            ->post('/admin/service-prices', [
                'service_id' => $home->id,
                'property_size_id' => $sedang->id,
                'price' => 99000,
            ])
            ->assertSessionHasErrors('property_size_id');
        $this->assertSame(
            1,
            ServicePrice::where('service_id', $home->id)->where('property_size_id', $sedang->id)->count()
        );

        // Lawn Mowing tidak melayani apartemen: quote ditolak
        $kombinasi = ['service_id' => $lawn->id, 'property_size_id' => $studio->id];
        $this->postJson('/api/orders/quote', $kombinasi)->assertStatus(422);

        // Admin menambah harganya -> kombinasi terbuka
        $this->post('/admin/service-prices', $kombinasi + ['price' => 90000])
            ->assertRedirect('/admin/service-prices');
        $this->postJson('/api/orders/quote', $kombinasi)
            ->assertOk()
            ->assertJsonPath('data.pricing.total_price', 90000);

        // Nonaktifkan harganya -> kombinasi tertutup lagi
        $baru = ServicePrice::where('service_id', $lawn->id)->where('property_size_id', $studio->id)->firstOrFail();
        $this->patch("/admin/service-prices/{$baru->id}/toggle")->assertRedirect('/admin/service-prices');
        $this->postJson('/api/orders/quote', $kombinasi)->assertStatus(422);
    }

    public function test_addon_baru_dan_nonaktif_memengaruhi_quote(): void
    {
        $this->masuk();
        $lawn = $this->jasa('Lawn Mowing');
        $kecil = $this->ukuran('Rumah Kecil');

        $this->post('/admin/service-addons', [
            'service_id' => $lawn->id,
            'addon_name' => 'Pangkas Tepi',
            'price' => 30000,
        ])->assertRedirect('/admin/service-addons');

        $addon = ServiceAddon::where('addon_name', 'Pangkas Tepi')->firstOrFail();
        $this->assertTrue((bool) $addon->is_active);

        $quote = [
            'service_id' => $lawn->id,
            'property_size_id' => $kecil->id,
            'addons' => [['service_addon_id' => $addon->id, 'quantity' => 2]],
        ];

        // Lawn Mowing Rumah Kecil 75000 + (30000 x 2)
        $this->postJson('/api/orders/quote', $quote)
            ->assertOk()
            ->assertJsonPath('data.pricing.total_price', 135000);

        // Nonaktifkan add-on -> quote yang memakainya ditolak
        $this->patch("/admin/service-addons/{$addon->id}/toggle")->assertRedirect('/admin/service-addons');
        $this->postJson('/api/orders/quote', $quote)->assertStatus(422);

        // Validasi: harga negatif dan jasa tidak ada
        $this->from('/admin/service-addons/create')
            ->post('/admin/service-addons', ['service_id' => $lawn->id, 'addon_name' => 'Gratis', 'price' => -5])
            ->assertSessionHasErrors('price');
        $this->from('/admin/service-addons/create')
            ->post('/admin/service-addons', ['service_id' => 999999, 'addon_name' => 'Hantu', 'price' => 1000])
            ->assertSessionHasErrors('service_id');
        $this->assertDatabaseMissing('service_addons', ['addon_name' => 'Gratis']);
        $this->assertDatabaseMissing('service_addons', ['addon_name' => 'Hantu']);
    }

    public function test_jenis_dan_ukuran_properti_validasi_luas_dan_nonaktif_menutup_di_api(): void
    {
        $this->masuk();

        $this->post('/admin/property-types', ['type_name' => 'Ruko'])->assertRedirect('/admin/property-types');
        $ruko = PropertyType::where('type_name', 'Ruko')->firstOrFail();

        // Nama jenis dobel ditolak
        $this->from('/admin/property-types/create')
            ->post('/admin/property-types', ['type_name' => 'Ruko'])
            ->assertSessionHasErrors('type_name');

        // Ukuran valid
        $this->post('/admin/property-sizes', [
            'property_type_id' => $ruko->id,
            'size_name' => 'Ruko Kecil',
            'min_area' => 20,
            'max_area' => 50,
        ])->assertRedirect('/admin/property-sizes');
        $this->assertDatabaseHas('property_sizes', [
            'property_type_id' => $ruko->id,
            'size_name' => 'Ruko Kecil',
            'is_active' => 1,
        ]);

        // Luas maksimum lebih kecil dari minimum ditolak
        $this->from('/admin/property-sizes/create')
            ->post('/admin/property-sizes', [
                'property_type_id' => $ruko->id,
                'size_name' => 'Ruko Besar',
                'min_area' => 50,
                'max_area' => 20,
            ])
            ->assertSessionHasErrors('max_area');
        $this->assertDatabaseMissing('property_sizes', ['size_name' => 'Ruko Besar']);

        // Hanya luas minimum diisi: boleh
        $this->post('/admin/property-sizes', [
            'property_type_id' => $ruko->id,
            'size_name' => 'Ruko Jumbo',
            'min_area' => 100,
        ])->assertRedirect('/admin/property-sizes');
        $this->assertDatabaseHas('property_sizes', ['size_name' => 'Ruko Jumbo']);

        // Jenis aktif tampil di API publik, dinonaktifkan -> hilang
        $nama = fn() => collect($this->getJson('/api/property-types')->assertOk()->json('data'))->pluck('type_name');
        $this->assertTrue($nama()->contains('Ruko'));

        $this->patch("/admin/property-types/{$ruko->id}/toggle")->assertRedirect('/admin/property-types');
        $this->assertFalse($nama()->contains('Ruko'));
    }
}