<?php
2: 
3: /**
4:  * Cashfree Webhook Simulator
5:  * This script simulates a server-to-server POST from Cashfree.
6:  * It correctly signs the payload using your CASHFREE_SECRET_KEY.
7:  */
8: 
9: require __DIR__ . '/../vendor/autoload.php';
10: $app = require_once __DIR__ . '/../bootstrap/app.php';
11: $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
12: 
13: $orderId = $argv[1] ?? null; // Pass your local webze- txn ID here
14: if (!$orderId) {
15:     die("Usage: php scratch/simulate_webhook.php [CASHFREE_ORDER_ID]\nExample: php scratch/simulate_webhook.php webze-1-1713170000\n");
16: }
17: 
18: $secretKey = env('CASHFREE_SECRET_KEY');
19: $appUrl    = env('APP_URL', 'http://localhost');
20: $webhookUrl= $appUrl . '/payment/webhook';
21: 
22: $timestamp = (string)(time() * 1000);
23: $payload = [
24:     "type" => "PAYMENT_SUCCESS_WEBHOOK",
25:     "data" => [
26:         "order" => [
27:             "order_id" => $orderId,
28:             "order_amount" => 1.00,
29:             "order_currency" => "INR",
30:             "order_status" => "PAID"
31:         ],
32:         "payment" => [
33:             "cf_payment_id" => "sim_" . rand(100000, 999999),
34:             "payment_status" => "SUCCESS",
35:             "payment_amount" => 1.00,
36:             "payment_currency" => "INR",
37:             "payment_message" => "Simulated Success",
38:             "payment_time" => date('Y-m-d H:i:s'),
39:             "payment_method" => ["upi" => ["channel" => "test"]]
40:         ],
41:         "customer_details" => [
42:             "customer_name" => "Test User",
43:             "customer_email" => "test@example.com",
44:             "customer_phone" => "9999999999"
45:         ]
46:     ],
47:     "event_time" => date('c')
48: ];
49: 
50: $jsonPayload = json_encode($payload);
51: $signedPayload = $timestamp . $jsonPayload;
52: $signature = base64_encode(hash_hmac('sha256', $signedPayload, $secretKey, true));
53: 
54: echo "Simulating Webhook for: $orderId\n";
55: echo "Target URL: $webhookUrl\n";
56: echo "--------------------------------------------------\n";
57: 
58: $ch = curl_init($webhookUrl);
59: curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
60: curl_setopt($ch, CURLOPT_POST, true);
61: curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
62: curl_setopt($ch, CURLOPT_HTTPHEADER, [
63:     'Content-Type: application/json',
64:     "x-webhook-timestamp: $timestamp",
65:     "x-webhook-signature: $signature"
66: ]);
67: 
68: $response = curl_exec($ch);
69: $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
70: curl_close($ch);
71: 
72: echo "Response Code: $httpCode\n";
73: echo "Response Body: $response\n";
74: echo "--------------------------------------------------\n";
75: if ($httpCode == 200) {
76:     echo "✅ SUCCESS: Webhook processed successfully.\n";
77: } else {
78:     echo "❌ FAILED: Webhook returned error code $httpCode.\n";
79: }
