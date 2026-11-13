<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\AdminOrderService;
use App\Support\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private AdminOrderService $orders)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(
            [
                'status' => ['nullable', Rule::in(array_keys(OrderStatus::TRANSITIONS))],
                'q' => ['nullable', 'string', 'max:100'],
            ],
            ['status.in' => 'Status filter tidak valid.', 'q.max' => 'Kata pencarian terlalu panjang.']
        );

        return view('admin.orders.index', [
            'orders' => $this->orders->orders($filters['status'] ?? null, $filters['q'] ?? null, 20),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        return view('admin.orders.show', ['order' => $this->orders->find($id)]);
    }

    public function forceComplete(Request $request, int $id): RedirectResponse
    {
        $kembali = route('admin.orders.show', ['id' => $id]);

        try {
            $order = $this->orders->forceComplete($id, $request->user());
        } catch (ApiException $e) {
            return back(302, [], $kembali)->with('error', $e->getMessage());
        }

        return back(302, [], $kembali)->with('success', "Pesanan {$order->order_code} ditandai selesai.");
    }
}