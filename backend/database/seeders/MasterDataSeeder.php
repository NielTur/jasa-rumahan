<?php

namespace Database\Seeders;

use App\Models\PropertySize;
use App\Models\PropertyType;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

// Katalog demo: kategori, jasa, jenis/ukuran properti, harga, add-on.
// Harga di sini cuma data awal. Setelah Admin Dashboard jadi (P5), harga diubah lewat form (A-4).
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // --- Kategori ---
        $katBersih = ServiceCategory::create([
            'category_name' => 'Kebersihan Rumah',
            'description' => 'Layanan pembersihan rumah dan hunian.',
            'is_active' => true,
        ]);
        $katTaman = ServiceCategory::create([
            'category_name' => 'Perawatan Taman',
            'description' => 'Layanan perawatan taman dan halaman.',
            'is_active' => true,
        ]);
        $katPohon = ServiceCategory::create([
            'category_name' => 'Perawatan Pohon',
            'description' => 'Layanan pemangkasan dan perawatan pohon.',
            'is_active' => true,
        ]);

        // --- Jasa utama ---
        $homeCleaning = Service::create([
            'category_id' => $katBersih->id,
            'service_name' => 'Home Cleaning',
            'description' => 'Pembersihan menyeluruh lantai, kamar, dapur, dan kamar mandi.',
            'duration_minutes' => 120,
            'is_active' => true,
        ]);
        $lawnMowing = Service::create([
            'category_id' => $katTaman->id,
            'service_name' => 'Lawn Mowing',
            'description' => 'Pemotongan rumput halaman sampai rapi.',
            'duration_minutes' => 90,
            'is_active' => true,
        ]);
        $treeTrimming = Service::create([
            'category_id' => $katPohon->id,
            'service_name' => 'Tree Trimming',
            'description' => 'Pemangkasan pohon dan ranting.',
            'duration_minutes' => 120,
            'is_active' => true,
        ]);

        // --- Jenis properti + ukuran ---
        $rumah = PropertyType::create(['type_name' => 'Rumah', 'is_active' => true]);
        $apartemen = PropertyType::create(['type_name' => 'Apartemen', 'is_active' => true]);

        $rumahKecil = PropertySize::create([
            'property_type_id' => $rumah->id,
            'size_name' => 'Rumah Kecil',
            'min_area' => 21,
            'max_area' => 45,
            'is_active' => true,
        ]);
        $rumahSedang = PropertySize::create([
            'property_type_id' => $rumah->id,
            'size_name' => 'Rumah Sedang',
            'min_area' => 46,
            'max_area' => 90,
            'is_active' => true,
        ]);
        $rumahBesar = PropertySize::create([
            'property_type_id' => $rumah->id,
            'size_name' => 'Rumah Besar',
            'min_area' => 91,
            'max_area' => 200,
            'is_active' => true,
        ]);
        $aptStudio = PropertySize::create([
            'property_type_id' => $apartemen->id,
            'size_name' => 'Apartemen Studio',
            'min_area' => 15,
            'max_area' => 30,
            'is_active' => true,
        ]);
        $apt2Kamar = PropertySize::create([
            'property_type_id' => $apartemen->id,
            'size_name' => 'Apartemen 2 Kamar',
            'min_area' => 31,
            'max_area' => 70,
            'is_active' => true,
        ]);

        // --- Harga: [jasa, ukuran, harga rupiah] ---
        // Lawn Mowing dan Tree Trimming sengaja tidak punya harga untuk apartemen.
        // Jadi kombinasi itu harus ditolak oleh /orders/quote nanti.
        $prices = [
            [$homeCleaning, $rumahKecil, 100000],
            [$homeCleaning, $rumahSedang, 150000],
            [$homeCleaning, $rumahBesar, 200000],
            [$homeCleaning, $aptStudio, 80000],
            [$homeCleaning, $apt2Kamar, 120000],

            [$lawnMowing, $rumahKecil, 75000],
            [$lawnMowing, $rumahSedang, 125000],
            [$lawnMowing, $rumahBesar, 200000],

            [$treeTrimming, $rumahKecil, 150000],
            [$treeTrimming, $rumahSedang, 250000],
            [$treeTrimming, $rumahBesar, 400000],
        ];

        foreach ($prices as [$service, $size, $price]) {
            ServicePrice::create([
                'service_id' => $service->id,
                'property_size_id' => $size->id,
                'price' => $price,
                'is_active' => true,
            ]);
        }

        // --- Add-on: [jasa, nama, harga] ---
        $addons = [
            [$homeCleaning, 'Pembersihan Sofa', 50000],
            [$homeCleaning, 'Pembersihan Kulkas', 35000],
            [$homeCleaning, 'Cuci Karpet', 60000],
            [$lawnMowing, 'Buang Sampah Rumput', 25000],
            [$treeTrimming, 'Angkut Ranting', 50000],
        ];

        foreach ($addons as [$service, $name, $price]) {
            ServiceAddon::create([
                'service_id' => $service->id,
                'addon_name' => $name,
                'price' => $price,
                'is_active' => true,
            ]);
        }
    }
}