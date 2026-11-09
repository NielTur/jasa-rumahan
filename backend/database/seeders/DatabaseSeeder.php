<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan penting: master dulu, baru akun (mitra butuh jasa)
        $this->call([
            MasterDataSeeder::class,
            DemoAccountSeeder::class,
        ]);
    }
}