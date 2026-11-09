<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CR-02 (G-02): payload mentah webhook dan data refund
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->json('gateway_payload')->nullable()->after('snap_token');
            $table->decimal('refund_amount', 12, 2)->nullable()->after('expired_at');
            $table->dateTime('refunded_at')->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_payload', 'refund_amount', 'refunded_at']);
        });
    }
};