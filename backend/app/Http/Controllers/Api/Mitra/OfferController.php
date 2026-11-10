<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Resources\MitraOfferResource;
use App\Http\Resources\OrderResource;
use App\Services\MitraOfferService;
use App\Support\ApiResponse;
use App\Support\CurrentMitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(private MitraOfferService $offers)
    {
    }

    // GET /mitra/offers
    public function index(Request $request): JsonResponse
    {
        $items = $this->offers->activeOffers(CurrentMitra::profile($request))
            ->map(fn($a) => (new MitraOfferResource($a))->resolve())
            ->values()
            ->all();

        return ApiResponse::success($items);
    }

    // POST /mitra/offers/{attempt_id}/accept
    public function accept(Request $request, int $attemptId): JsonResponse
    {
        $order = $this->offers->accept(CurrentMitra::profile($request), $attemptId);

        // Setelah accept, alamat dan telepon customer boleh tampil (D-12)
        return ApiResponse::success(
            (new OrderResource($order))->resolve(),
            'Pesanan diterima. Menunggu pembayaran customer.'
        );
    }

    // POST /mitra/offers/{attempt_id}/reject
    public function reject(Request $request, int $attemptId): JsonResponse
    {
        $this->offers->reject(CurrentMitra::profile($request), $attemptId);

        return ApiResponse::success(null, 'Tawaran ditolak.');
    }
}