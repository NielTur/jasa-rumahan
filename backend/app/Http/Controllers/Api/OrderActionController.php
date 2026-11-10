<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderActionRequest;
use App\Http\Resources\OrderResource;
use App\Models\User;
use App\Services\CustomerOrderService;
use App\Services\OrderAccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

// Aksi customer/guest pada satu pesanan. Route ini tidak memakai middleware auth (guest boleh),
// jadi token dibaca manual lewat $request->user('sanctum').
class OrderActionController extends Controller
{
    public function __construct(
        private OrderAccessService $access,
        private CustomerOrderService $orders,
    ) {
    }

    // POST /orders/{order_code}/cancel   body: phone (guest), reason (opsional)
    public function cancel(OrderActionRequest $request, string $orderCode): JsonResponse
    {
        $user = $request->user('sanctum');
        $order = $this->access->find($orderCode, $user, $request->input('phone'));

        $order = $this->orders->cancel($order, $this->customerOrNull($user), $request->input('reason'));

        return ApiResponse::success((new OrderResource($order))->resolve(), 'Pesanan dibatalkan.');
    }

    // POST /orders/{order_code}/confirm-completion   body: phone (guest)
    public function confirmCompletion(OrderActionRequest $request, string $orderCode): JsonResponse
    {
        $user = $request->user('sanctum');
        $order = $this->access->find($orderCode, $user, $request->input('phone'));

        $order = $this->orders->confirmCompletion($order, $this->customerOrNull($user));

        return ApiResponse::success((new OrderResource($order))->resolve(), 'Pesanan dikonfirmasi selesai.');
    }

    // Riwayat status hanya mencatat user kalau yang login memang customer. Guest dan role lain = NULL.
    private function customerOrNull(?User $user): ?User
    {
        return $user && $user->role === 'customer' ? $user : null;
    }
}