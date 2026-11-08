<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('service_addon_id')->constrained('service_addons');
            $table->string('addon_name', 100);               // snapshot
            $table->decimal('unit_price', 12, 2);            // snapshot
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);              // snapshot
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addons');
    }
};