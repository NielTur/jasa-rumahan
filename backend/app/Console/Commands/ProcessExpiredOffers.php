<?php

namespace App\Console\Commands;

use App\Services\MatchingService;
use Illuminate\Console\Command;

class ProcessExpiredOffers extends Command
{
    protected $signature = 'orders:process-expired-offers';

    protected $description = 'Tandai offer mitra yang kedaluwarsa, lalu tawarkan ke mitra berikutnya atau batalkan pesanan';

    public function handle(MatchingService $matching): int
    {
        $count = $matching->processAllExpired();
        $this->info("Pesanan diproses: {$count}");

        return self::SUCCESS;
    }
}