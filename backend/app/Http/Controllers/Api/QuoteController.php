<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest;
use App\Services\PricingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

// POST /api/orders/quote: hitung rincian harga, tidak menyimpan apa pun.
class QuoteController extends Controller
{
    public function __construct(private PricingService $pricing)
    {
    }

    public function store(QuoteRequest $request): JsonResponse
    {
        $result = $this->pricing->quote(
            $request->integer('service_id'),
            $request->integer('property_size_id'),
            $request->input('addons', [])
        );

        return ApiResponse::success($result, 'Rincian harga berhasil dihitung.');
    }
}