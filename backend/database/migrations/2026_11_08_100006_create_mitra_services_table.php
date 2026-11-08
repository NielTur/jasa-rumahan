<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mitra_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra_profiles');
            $table->foreignId('service_id')->constrained('services');
            $table->string('status', 20); // active | inactive
            $table->timestamp('created_at')->useCurrent();

            // Satu mitra tidak boleh mendaftarkan jasa yang sama dua kali
            $table->unique(['mitra_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_services');
    }
};