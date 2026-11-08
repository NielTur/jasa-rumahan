<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('property_size_id')->constrained('property_sizes');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Satu kombinasi jasa + ukuran hanya punya satu harga
            $table->unique(['service_id', 'property_size_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};