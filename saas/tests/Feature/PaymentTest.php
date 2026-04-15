<?php
6: 
namespace Tests\Feature;
7: 
use App\Models\Transaction;
8: use App\Models\User;
9: use App\Services\CashfreeService;
10: use Illuminate\Foundation\Testing\RefreshDatabase;
11: use Tests\TestCase;
12: use Mockery;
13: 
14: class PaymentTest extends TestCase
15: {
16:     use RefreshDatabase;
17: 
18:     public function test_can_create_payment_order()
19:     {
20:         $user = User::factory()->create();
21:         $this->actingAs($user);
22: 
23:         // Mock CashfreeService
24:         $this->mock(CashfreeService::class, function ($mock) {
25:             $mock->shouldReceive('createOrder')
26:                 ->once()
27:                 ->andReturn([
28:                     'payment_session_id' => 'session_test_123',
29:                     'order_id'           => 'webze-test-order'
30:                 ]);
31:         });
32: 
33:         $response = $this->postJson('/payment/create-order', [
34:             'plan'   => 'monthly',
35:             'amount' => 999,
36:         ]);
37: 
38:         $response->assertStatus(200)
39:             ->assertJsonStructure(['payment_session_id', 'cashfree_order_id']);
40: 
41:         $this->assertDatabaseHas('transactions', [
42:             'user_id' => $user->id,
43:             'amount'  => 999.00,
44:             'status'  => 'pending',
45:         ]);
46:     }
47: 
48:     public function test_can_handle_successful_payment_webhook()
49:     {
50:         $user = User::factory()->create();
51:         $transaction = Transaction::create([
52:             'user_id'           => $user->id,
53:             'amount'            => 999,
54:             'status'            => 'pending',
55:             'cashfree_order_id' => 'order_test_123'
56:         ]);
57: 
58:         // Mock signature verification
59:         $this->mock(CashfreeService::class, function ($mock) {
60:             $mock->shouldReceive('verifyWebhookSignature')->andReturn(true);
61:         });
62: 
63:         $payload = [
64:             'type' => 'PAYMENT_SUCCESS_WEBHOOK',
65:             'data' => [
66:                 'order' => ['order_id' => 'order_test_123', 'order_status' => 'PAID'],
67:                 'payment' => ['cf_payment_id' => 'cf_123', 'payment_status' => 'SUCCESS']
68:             ]
69:         ];
70: 
71:         $response = $this->postJson('/payment/webhook', $payload);
72: 
73:         $response->assertStatus(200);
74:         $this->assertEquals('paid', $transaction->fresh()->status);
75:     }
76: }
