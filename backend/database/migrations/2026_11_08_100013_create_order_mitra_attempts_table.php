<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_mitra_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('mitra_id')->constrained('mitra_profiles');
            $table->unsignedInteger('attempt_number');
            $table->string('status', 20); // offered | accepted | rejected | expired
            $table->dateTime('offered_at');
            $table->dateTime('responded_at')->nullable();

            // Mitra yang sama tidak ditawari dua kali, nomor percobaan tidak ganda
            $table->unique(['order_id', 'mitra_id']);
            $table->unique(['order_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_mitra_attempts');
    }
};