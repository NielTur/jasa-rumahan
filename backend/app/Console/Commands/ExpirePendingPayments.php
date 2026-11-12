<?php

namespace App\Console\Commands;

use App\Services\OrderExpiryService;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Kedaluwarkan pembayaran pending yang lewat batas, lalu batalkan pesanan yang batas bayarnya habis';

    public function handle(OrderExpiryService $expiry): int
    {
        $count = $expiry->expirePendingPayments();
        $this->info("Pesanan diproses: {$count}");

        return self::SUCCESS;
    }
}