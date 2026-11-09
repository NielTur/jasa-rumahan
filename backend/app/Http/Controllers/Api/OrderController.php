<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\TrackOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderAccessService;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private OrderAccessService $access,
    ) {
    }

    // POST /orders: guest (tanpa token) atau customer login (dengan token)
    public function store(CreateOrderRequest $request): JsonResponse
    {
        // 'sanctum' dibaca manual karena route ini tidak memakai middleware auth (guest boleh)
        $order = $this->orders->create($request->validated(), $request->user('sanctum'));

        return ApiResponse::success(
            (new OrderResource($order))->resolve(),
            'Pesanan berhasil dibuat. Sedang mencari mitra.',
            201
        );
    }

    // POST /orders/track: guest kirim order_code + phone tiap request (stateless, D-13)
    public function track(TrackOrderRequest $request): JsonResponse
    {
        $order = $this->access->find(
            $request->input('order_code'),
            $request->user('sanctum'),
            $request->input('phone')
        );

        return ApiResponse::success((new OrderResource($order))->resolve());
    }

    // GET /orders/{order_code}: customer login, pemilik saja
    public function show(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', strtoupper($orderCode))
            ->where('customer_id', $request->user()->id)
            ->first();

        if (!$order) {
            throw new ApiException('Pesanan tidak ditemukan.', 404);
        }

        return ApiResponse::success((new OrderResource($order))->resolve());
    }

    // GET /customer/orders: riwayat customer login (paginasi)
    public function myOrders(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $page = Order::where('customer_id', $request->user()->id)
            ->with(['service', 'propertySize', 'addons', 'mitra.user', 'payments'])
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = $page->getCollection()
            ->map(fn($o) => (new OrderResource($o))->resolve())
            ->all();

        return ApiResponse::paginated($items, $page);
    }

    // GET /orders/{order_code}/status-history: pemilik (token) atau guest (?phone=08...)
    public function history(Request $request, string $orderCode): JsonResponse
    {
        $order = $this->access->find($orderCode, $request->user('sanctum'), $request->query('phone'));

        $rows = $order->statusHistories()
            ->orderBy('id')
            ->get()
            ->map(fn($h) => [
                'old_status' => $h->old_status,
                'new_status' => $h->new_status,
                'note' => $h->note,
                'created_at' => $h->created_at->toIso8601String(),
            ])
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }
}