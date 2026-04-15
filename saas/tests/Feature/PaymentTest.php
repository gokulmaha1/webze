<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\CashfreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_payment_order()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock CashfreeService
        $this->mock(CashfreeService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'payment_session_id' => 'session_test_123',
                    'order_id'           => 'webze-test-order'
                ]);
        });

        $response = $this->postJson('/payment/create-order', [
            'plan'   => 'monthly',
            'amount' => 999,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['payment_session_id', 'cashfree_order_id']);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount'  => 999.00,
            'status'  => 'pending',
        ]);
    }

    public function test_can_handle_successful_payment_webhook()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create([
            'user_id'           => $user->id,
            'amount'            => 999,
            'status'            => 'pending',
            'cashfree_order_id' => 'order_test_123'
        ]);

        // Mock signature verification
        $this->mock(CashfreeService::class, function ($mock) {
            $mock->shouldReceive('verifyWebhookSignature')->andReturn(true);
        });

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => ['order_id' => 'order_test_123', 'order_status' => 'PAID'],
                'payment' => ['cf_payment_id' => 'cf_123', 'payment_status' => 'SUCCESS']
            ]
        ];

        $response = $this->postJson('/payment/webhook', $payload);

        $response->assertStatus(200);
        $this->assertEquals('paid', $transaction->fresh()->status);
    }
}
