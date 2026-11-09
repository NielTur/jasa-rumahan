<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // Jalankan scheduler dengan: php artisan schedule:work
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('orders:process-expired-offers')->everyMinute();
        // Nanti (P4): payments:expire-pending tiap menit, orders:auto-complete tiap jam
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}