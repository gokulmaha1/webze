<?php

/**
 * Cashfree Webhook Simulator
 * This script simulates a server-to-server POST from Cashfree.
 * It correctly signs the payload using your CASHFREE_SECRET_KEY.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$orderId = $argv[1] ?? null; // Pass your local webze- txn ID here
if (!$orderId) {
    die("Usage: php scratch/simulate_webhook.php [CASHFREE_ORDER_ID]\nExample: php scratch/simulate_webhook.php webze-1-1713170000\n");
}

$secretKey = env('CASHFREE_SECRET_KEY');
$appUrl    = env('APP_URL', 'http://localhost');
$webhookUrl= $appUrl . '/payment/webhook';

$timestamp = (string)(time() * 1000);
$payload = [
    "type" => "PAYMENT_SUCCESS_WEBHOOK",
    "data" => [
        "order" => [
            "order_id" => $orderId,
            "order_amount" => 1.00,
            "order_currency" => "INR",
            "order_status" => "PAID"
        ],
        "payment" => [
            "cf_payment_id" => "sim_" . rand(100000, 999999),
            "payment_status" => "SUCCESS",
            "payment_amount" => 1.00,
            "payment_currency" => "INR",
            "payment_message" => "Simulated Success",
            "payment_time" => date('Y-m-d H:i:s'),
            "payment_method" => ["upi" => ["channel" => "test"]]
        ],
        "customer_details" => [
            "customer_name" => "Test User",
            "customer_email" => "test@example.com",
            "customer_phone" => "9999999999"
        ]
    ],
    "event_time" => date('c')
];

$jsonPayload = json_encode($payload);
$signedPayload = $timestamp . $jsonPayload;
$signature = base64_encode(hash_hmac('sha256', $signedPayload, $secretKey, true));

echo "Simulating Webhook for: $orderId\n";
echo "Target URL: $webhookUrl\n";
echo "--------------------------------------------------\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "x-webhook-timestamp: $timestamp",
    "x-webhook-signature: $signature"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body: $response\n";
echo "--------------------------------------------------\n";
if ($httpCode == 200) {
    echo "✅ SUCCESS: Webhook processed successfully.\n";
} else {
    echo "❌ FAILED: Webhook returned error code $httpCode.\n";
}
