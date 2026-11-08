<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mitra_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Minggu ... 6 = Sabtu (D-09)
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_availabilities');
    }
};