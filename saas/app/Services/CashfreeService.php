<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CashfreeService
{
    private string $appId;
    private string $secretKey;
    private string $baseUrl;
    private string $apiVersion = '2023-08-01';

    public function __construct()
    {
        $this->appId     = config('cashfree.app_id');
        $this->secretKey = config('cashfree.secret_key');
        $env             = config('cashfree.env', 'production');
        $this->baseUrl   = $env === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Returns the common HTTP headers required by Cashfree API.
     */
    private function headers(): array
    {
        return [
            'x-api-version'    => $this->apiVersion,
            'x-client-id'      => $this->appId,
            'x-client-secret'  => $this->secretKey,
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
        ];
    }

    /**
     * Create a new Cashfree payment order.
     *
     * @param  string  $orderId        Unique order ID (e.g. webze-txn-123-timestamp)
     * @param  float   $amount         Amount in INR
     * @param  string  $customerName
     * @param  string  $customerEmail
     * @param  string  $customerPhone
     * @param  string  $returnUrl      URL Cashfree redirects to after payment
     * @param  array   $meta           Extra meta data stored in order_tags
     * @return array                   Cashfree response including payment_session_id
     */
    public function createOrder(
        string $orderId,
        float  $amount,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $returnUrl,
        array  $meta = []
    ): array {
        $payload = [
            'order_id'       => $orderId,
            'order_amount'   => round($amount, 2),
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => 'cust_' . preg_replace('/\D/', '', $customerPhone),
                'customer_name'  => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
            ],
            'order_meta' => [
                'return_url'   => $returnUrl . '?order_id={order_id}',
                'notify_url'   => route('payment.webhook'),
            ],
        ];

        if (!empty($meta)) {
            $payload['order_tags'] = $meta;
        }

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/orders", $payload);

        if ($response->failed()) {
            Log::error('Cashfree createOrder failed', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'order_id' => $orderId,
            ]);
            throw new \Exception('Cashfree order creation failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch order details from Cashfree (used to verify after callback).
     */
    public function getOrderStatus(string $cashfreeOrderId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/orders/{$cashfreeOrderId}");

        if ($response->failed()) {
            Log::error('Cashfree getOrderStatus failed', [
                'status'   => $response->status(),
                'order_id' => $cashfreeOrderId,
            ]);
            throw new \Exception('Cashfree order fetch failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch payment details for an order.
     */
    public function getPaymentsForOrder(string $cashfreeOrderId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/orders/{$cashfreeOrderId}/payments");

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Verify the webhook signature sent by Cashfree.
     * Cashfree signs the (timestamp + raw_body) with HMAC-SHA256 using secret key.
     *
     * @param  string $rawBody    Raw request body string
     * @param  string $timestamp  From header: x-webhook-timestamp
     * @param  string $signature  From header: x-webhook-signature
     */
    public function verifyWebhookSignature(string $rawBody, string $timestamp, string $signature): bool
    {
        $signedPayload   = $timestamp . $rawBody;
        $expectedSig     = base64_encode(hash_hmac('sha256', $signedPayload, $this->secretKey, true));
        return hash_equals($expectedSig, $signature);
    }

    /**
     * Create a new Cashfree shareable payment link.
     */
    public function createPaymentLink(
        string $linkId,
        float  $amount,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $purpose
    ): array {
        $payload = [
            'link_id'       => $linkId,
            'link_amount'   => round($amount, 2),
            'link_currency' => 'INR',
            'link_purpose'  => $purpose,
            'customer_details' => [
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail,
                'customer_name'  => $customerName,
            ],
            'link_notify' => [
                'send_sms'   => false,
                'send_email' => false,
            ],
            'link_meta' => [
                'return_url' => route('payment.callback') . '?link_id={link_id}',
            ],
        ];

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/links", $payload);

        if ($response->failed()) {
            Log::error('Cashfree createPaymentLink failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'link_id'=> $linkId,
            ]);
            throw new \Exception('Cashfree link creation failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Helper: generate a unique Webze order ID.
     */
    public static function generateOrderId(int $transactionId): string
    {
        return 'webze-' . $transactionId . '-' . time();
    }
}
