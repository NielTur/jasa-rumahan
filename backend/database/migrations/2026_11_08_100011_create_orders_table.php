<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 30)->unique();

            // customer_id NULL = guest. mitra_id NULL = masih dicari.
            // mitra_id = mitra_profiles.id, BUKAN users.id (B-11)
            $table->foreignId('customer_id')->nullable()->constrained('users');
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses');
            $table->foreignId('mitra_id')->nullable()->constrained('mitra_profiles');
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('property_size_id')->constrained('property_sizes');

            $table->dateTime('scheduled_at');
            $table->unsignedInteger('duration_minutes')->nullable();

            // Snapshot data customer
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->text('service_address');
            $table->text('address_notes')->nullable();

            // Snapshot harga
            $table->decimal('service_price', 12, 2);
            $table->decimal('addons_total', 12, 2);
            $table->decimal('total_price', 12, 2);

            $table->string('order_status', 30);
            $table->text('customer_note')->nullable();
            $table->timestamps();

            // Indeks sesuai dokumen desain 15.14 (kolom FK sudah otomatis terindeks)
            $table->index('customer_phone');
            $table->index('order_status');
            $table->index('scheduled_at'); // dipakai cek bentrok jadwal
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};