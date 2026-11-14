<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MidtransPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(private MidtransPaymentService $payments)
    {
    }

    // POST /webhooks/midtrans. Hanya signature salah yang ditolak; notifikasi valid selalu ACK 200.
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (!$this->payments->isValidSignature($payload)) {
            Log::warning('Signature webhook Midtrans tidak valid.', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $this->payments->processNotification($payload);

        return response()->json(['message' => 'OK.'], 200);
    }
}
