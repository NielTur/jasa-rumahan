<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\AdminOrderService;
use App\Support\PaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private AdminOrderService $orders)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(
            [
                'status' => ['nullable', Rule::in(PaymentStatus::ALL)],
                'q' => ['nullable', 'string', 'max:100'],
            ],
            ['status.in' => 'Status filter tidak valid.', 'q.max' => 'Kata pencarian terlalu panjang.']
        );

        return view('admin.payments.index', [
            'payments' => $this->orders->payments($filters['status'] ?? null, $filters['q'] ?? null, 20),
            'filters' => $filters,
        ]);
    }

    // Tandai hasil refund manual: refunded atau refund_failed
    public function updateRefund(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(
            ['status' => ['required', Rule::in([PaymentStatus::REFUNDED, PaymentStatus::REFUND_FAILED])]],
            ['status.required' => 'Status wajib diisi.', 'status.in' => 'Status refund tidak valid.']
        );

        $kembali = route('admin.payments.index');

        try {
            $payment = $this->orders->markRefund($id, $data['status']);
        } catch (ApiException $e) {
            return back(302, [], $kembali)->with('error', $e->getMessage());
        }

        return back(302, [], $kembali)->with(
            'success',
            'Pembayaran ' . $payment->gateway_order_id . ' ditandai: ' . PaymentStatus::label($payment->payment_status) . '.'
        );
    }
}