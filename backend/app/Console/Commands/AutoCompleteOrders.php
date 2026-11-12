<?php

namespace App\Console\Commands;

use App\Services\OrderExpiryService;
use Illuminate\Console\Command;

class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete';

    protected $description = 'Selesaikan otomatis pesanan mitra_completed yang tidak dikonfirmasi customer sampai batas waktu';

    public function handle(OrderExpiryService $expiry): int
    {
        $count = $expiry->autoCompleteDue();
        $this->info("Pesanan diproses: {$count}");

        return self::SUCCESS;
    }
}