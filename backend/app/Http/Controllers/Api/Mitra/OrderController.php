<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\MitraOrderService;
use App\Support\ApiResponse;
use App\Support\CurrentMitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private MitraOrderService $orders)
    {
    }

    // GET /mitra/orders?status=confirmed,on_the_way&page=1&per_page=15
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $status = $request->query('status');

        $page = $this->orders->paginate(
            CurrentMitra::profile($request),
            is_string($status) ? $status : null,
            $perPage
        );

        $items = $page->getCollection()
            ->map(fn($o) => (new OrderResource($o))->resolve())
            ->values()
            ->all();

        return ApiResponse::paginated($items, $page);
    }

    // GET /mitra/orders/{order_code}: alamat lengkap + telepon customer tampil di sini
    public function show(Request $request, string $orderCode): JsonResponse
    {
        $order = $this->orders->find(CurrentMitra::profile($request), $orderCode);

        return ApiResponse::success((new OrderResource($order))->resolve());
    }

    // POST /mitra/orders/{order_code}/depart: confirmed -> on_the_way
    public function depart(Request $request, string $orderCode): JsonResponse
    {
        return $this->progress($request, $orderCode, 'depart', 'Berangkat menuju lokasi dicatat.');
    }

    // POST /mitra/orders/{order_code}/arrive: on_the_way -> arrived
    public function arrive(Request $request, string $orderCode): JsonResponse
    {
        return $this->progress($request, $orderCode, 'arrive', 'Tiba di lokasi dicatat.');
    }

    // POST /mitra/orders/{order_code}/start: arrived -> in_progress
    public function start(Request $request, string $orderCode): JsonResponse
    {
        return $this->progress($request, $orderCode, 'start', 'Pengerjaan dimulai.');
    }

    // POST /mitra/orders/{order_code}/finish: in_progress -> mitra_completed
    public function finish(Request $request, string $orderCode): JsonResponse
    {
        return $this->progress(
            $request,
            $orderCode,
            'finish',
            'Pekerjaan ditandai selesai. Menunggu konfirmasi customer.'
        );
    }

    private function progress(Request $request, string $orderCode, string $action, string $message): JsonResponse
    {
        $order = $this->orders->progress(CurrentMitra::profile($request), $orderCode, $action);

        return ApiResponse::success((new OrderResource($order))->resolve(), $message);
    }
}