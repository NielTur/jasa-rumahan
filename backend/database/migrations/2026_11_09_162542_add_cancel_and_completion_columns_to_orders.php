<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CR-01 (G-01): catatan pembatalan dan penyelesaian pesanan
return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('cancelled_at')->nullable()->after('customer_note');
            $table->text('cancel_reason')->nullable()->after('cancelled_at');
            $table->string('cancelled_by', 20)->nullable()->after('cancel_reason'); // customer | mitra | admin | system
            $table->dateTime('completed_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancel_reason', 'cancelled_by', 'completed_at']);
        });
    }
};