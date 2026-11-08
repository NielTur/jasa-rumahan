<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_type_id')->constrained('property_types');
            $table->string('size_name', 100);
            $table->decimal('min_area', 10, 2)->nullable(); // m2
            $table->decimal('max_area', 10, 2)->nullable(); // m2
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_sizes');
    }
};