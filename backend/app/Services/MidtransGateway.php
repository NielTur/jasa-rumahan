<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransGateway
{
    private function configure(): void
    {
        Config::$serverKey = (string) config('marketplace.midtrans.server_key');
        Config::$isProduction = (bool) config('marketplace.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapTransaction(array $parameters): object
    {
        $this->configure();

        return Snap::createTransaction($parameters);
    }

    public function fetchTransactionStatus(string $gatewayOrderId): object
    {
        $this->configure();

        return Transaction::status($gatewayOrderId);
    }
}
