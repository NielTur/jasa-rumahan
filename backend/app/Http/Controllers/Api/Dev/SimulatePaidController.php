<?php

namespace App\Http\Controllers\Api\Dev;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\DevPaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

// KHUSUS DEVELOPMENT. Pagar APP_ENV=local ada di DevPaymentService.
class SimulatePaidController extends Controller
{
    public function __construct(private DevPaymentService $dev)
    {
    }

    // POST /dev/orders/{order_code}/simulate-paid
    public function store(string $orderCode): JsonResponse
    {
        $order = $this->dev->simulatePaid($orderCode);

        return ApiResponse::success(
            (new OrderResource($order))->resolve(),
            'Pembayaran disimulasikan (khusus dev).'
        );
    }
}