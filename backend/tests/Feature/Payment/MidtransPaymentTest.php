<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Services\MidtransGateway;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\BuildsMitraFixtures;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMitraFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
        config([
            'marketplace.midtrans.server_key' => 'SB-Mid-server-test',
            'marketplace.midtrans.is_production' => false,
        ]);
    }

    private function makeWaitingOrder(): Order
    {
        $order = $this->makeOrder(null, OrderStatus::WAITING_PAYMENT);
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => OrderStatus::SEARCHING_MITRA,
            'new_status' => OrderStatus::WAITING_PAYMENT,
            'note' => 'Mitra menerima pesanan.',
        ]);

        return $order;
    }

    private function notification(string $orderId, string $status = 'settlement', string $amount = '150000.00'): array
    {
        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $amount,
            'transaction_status' => $status,
            'fraud_status' => 'accept',
            'transaction_id' => 'MIDTRANS-TRX-1',
            'payment_type' => 'bank_transfer',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('marketplace.midtrans.server_key')
        );

        return $payload;
    }

    public function test_guest_can_create_snap_using_server_side_amount(): void
    {
        $order = $this->makeWaitingOrder();
        $gateway = Mockery::mock(MidtransGateway::class);
        $gateway->shouldReceive('createSnapTransaction')
            ->once()
            ->with(Mockery::on(fn ($params) => $params['transaction_details']['gross_amount'] === 150000
                && $params['transaction_details']['order_id'] === $order->order_code . '-1'))
            ->andReturn((object) [
                'token' => 'snap-token-test',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test',
            ]);
        $this->app->instance(MidtransGateway::class, $gateway);

        $this->postJson("/api/orders/{$order->order_code}/payments", ['phone' => $order->customer_phone])
            ->assertOk()
            ->assertJsonPath('data.snap_token', 'snap-token-test')
            ->assertJsonPath('data.gateway_order_id', $order->order_code . '-1');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => 150000,
            'payment_status' => 'pending',
            'snap_token' => 'snap-token-test',
        ]);
    }

    public function test_valid_settlement_webhook_confirms_order_and_is_idempotent(): void
    {
        $order = $this->makeWaitingOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => 150000,
            'payment_status' => 'pending',
        ]);
        $payload = $this->notification($payment->gateway_order_id);

        $this->postJson('/api/webhooks/midtrans', $payload)->assertOk();
        $this->postJson('/api/webhooks/midtrans', $payload)->assertOk();

        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->order_status);
        $this->assertSame(1, $order->statusHistories()->where('new_status', OrderStatus::CONFIRMED)->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $order = $this->makeWaitingOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => 150000,
            'payment_status' => 'pending',
        ]);
        $payload = $this->notification($payment->gateway_order_id);
        $payload['signature_key'] = 'invalid';

        $this->postJson('/api/webhooks/midtrans', $payload)->assertUnauthorized();
        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);
    }

    public function test_old_pending_notification_does_not_downgrade_paid_payment(): void
    {
        $order = $this->makeWaitingOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => 150000,
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->postJson('/api/webhooks/midtrans', $this->notification($payment->gateway_order_id, 'pending'))->assertOk();
        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_amount_mismatch_does_not_update_payment_but_returns_ack(): void
    {
        $order = $this->makeWaitingOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'midtrans',
            'gateway_order_id' => $order->order_code . '-1',
            'amount' => 150000,
            'payment_status' => 'pending',
        ]);

        $this->postJson('/api/webhooks/midtrans', $this->notification($payment->gateway_order_id, 'settlement', '1.00'))
            ->assertOk();
        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertSame(OrderStatus::WAITING_PAYMENT, $order->fresh()->order_status);
    }
}
