<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\CashfreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private CashfreeService $cashfree;

    public function __construct(CashfreeService $cashfree)
    {
        $this->cashfree = $cashfree;
    }

    // ──────────────────────────────────────────────────────────────────────
    // 1. CREATE ORDER
    //    Called by the frontend when user picks a plan.
    //    POST /payment/create-order
    // ──────────────────────────────────────────────────────────────────────
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'plan'   => 'required|in:monthly,yearly,one_time',
            'amount' => 'required|numeric|min:1',
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first(); // fallback for API-driven calls

        // 1. Create a pending Transaction record first to get an ID
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount'  => $validated['amount'],
            'status'  => 'pending',
            'plan'    => $validated['plan'],
            'currency'=> 'INR',
        ]);

        // 2. Generate a unique Cashfree order ID
        $cashfreeOrderId = CashfreeService::generateOrderId($transaction->id);

        // 3. Call Cashfree API
        try {
            $orderData = $this->cashfree->createOrder(
                orderId:       $cashfreeOrderId,
                amount:        $validated['amount'],
                customerName:  $user->name,
                customerEmail: $user->email,
                customerPhone: $user->phone ?? '9999999999',
                returnUrl:     route('payment.callback'),
                meta:          [
                    'plan'           => $validated['plan'],
                    'transaction_id' => (string) $transaction->id,
                    'user_id'        => (string) $user->id,
                ]
            );
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // 4. Store Cashfree order ID against our transaction
        $transaction->update([
            'cashfree_order_id' => $cashfreeOrderId,
        ]);

        return response()->json([
            'success'            => true,
            'payment_session_id' => $orderData['payment_session_id'],
            'cashfree_order_id'  => $cashfreeOrderId,
            'transaction_id'     => $transaction->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 2. CALLBACK
    //    Cashfree redirects here after payment (GET redirect, not a webhook).
    //    GET /payment/callback?order_id={cashfree_order_id}
    // ──────────────────────────────────────────────────────────────────────
    public function callback(Request $request)
    {
        $cashfreeOrderId = $request->query('order_id');

        if (!$cashfreeOrderId) {
            return redirect('/')->with('error', 'Invalid payment callback.');
        }

        // Verify order status with Cashfree (never trust the redirect alone)
        try {
            $orderStatus = $this->cashfree->getOrderStatus($cashfreeOrderId);
            $payments    = $this->cashfree->getPaymentsForOrder($cashfreeOrderId);
        } catch (\Exception $e) {
            Log::error('Cashfree callback fetch failed', ['order_id' => $cashfreeOrderId, 'error' => $e->getMessage()]);
            return redirect('/')->with('error', 'Could not verify payment. Please contact support.');
        }

        $transaction = Transaction::where('cashfree_order_id', $cashfreeOrderId)->first();

        if (!$transaction) {
            Log::warning('Cashfree callback: transaction not found', ['order_id' => $cashfreeOrderId]);
            return redirect('/')->with('error', 'Transaction not found.');
        }

        $cfStatus     = strtoupper($orderStatus['order_status'] ?? 'UNKNOWN');
        $paymentId    = $payments[0]['cf_payment_id'] ?? null;
        $paymentStatus= $payments[0]['payment_status'] ?? null;

        $newStatus = match ($cfStatus) {
            'PAID'    => 'paid',
            'EXPIRED' => 'failed',
            'ACTIVE'  => 'pending',
            default   => 'pending',
        };

        $transaction->update([
            'status'                  => $newStatus,
            'cashfree_payment_id'     => $paymentId,
            'cashfree_payment_status' => $paymentStatus,
        ]);

        Log::info('Cashfree callback processed', [
            'cashfree_order_id' => $cashfreeOrderId,
            'status'            => $newStatus,
        ]);

        if ($newStatus === 'paid') {
            return redirect('/admin')->with('success', '✅ Payment successful! Your plan is now active.');
        }

        return redirect('/admin')->with('warning', 'Payment not completed. Status: ' . $cfStatus);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 3. WEBHOOK
    //    Cashfree server-to-server notification (more reliable than callback).
    //    POST /payment/webhook  — CSRF exempt
    // ──────────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $rawBody  = $request->getContent();
        $timestamp= $request->header('x-webhook-timestamp', '');
        $signature= $request->header('x-webhook-signature', '');

        // Verify signature
        if (!$this->cashfree->verifyWebhookSignature($rawBody, $timestamp, $signature)) {
            Log::warning('Cashfree webhook: invalid signature', ['raw' => substr($rawBody, 0, 200)]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data   = json_decode($rawBody, true);
        $type   = $data['type'] ?? '';
        $payload= $data['data'] ?? [];

        Log::info('Cashfree webhook received', ['type' => $type]);

        // Handle payment events
        if (in_array($type, ['PAYMENT_SUCCESS_WEBHOOK', 'PAYMENT_FAILED_WEBHOOK', 'LINK_PAID_WEBHOOK'])) {
            $cashfreeOrderId  = $payload['order']['order_id'] ?? null;
            $cashfreeLinkId   = $payload['link_id'] ?? null;

            $transaction = null;
            if ($cashfreeOrderId) {
                $transaction = Transaction::where('cashfree_order_id', $cashfreeOrderId)->first();
            } elseif ($cashfreeLinkId) {
                $transaction = Transaction::where('cashfree_link_id', $cashfreeLinkId)->first();
            }

            if (!$transaction) {
                Log::error('Cashfree webhook: transaction not found', ['order_id' => $cashfreeOrderId, 'link_id' => $cashfreeLinkId]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Skip if already in final state
            if ($transaction->status === 'paid') {
                return response()->json(['message' => 'Already processed']);
            }

            $newStatus = match ($type) {
                'PAYMENT_SUCCESS_WEBHOOK' => 'paid',
                'LINK_PAID_WEBHOOK'       => 'paid',
                'PAYMENT_FAILED_WEBHOOK'  => 'failed',
                default                   => 'pending',
            };

            $transaction->update([
                'status'                  => $newStatus,
                'cashfree_payment_id'     => $payload['payment']['cf_payment_id'] ?? null,
                'cashfree_payment_status' => $payload['payment']['payment_status'] ?? null,
                'cashfree_raw_response'   => $rawBody,
            ]);

            // AUTOMATIC WEBSITE ACTIVATION
            if ($newStatus === 'paid' && $transaction->website_id) {
                $website = \App\Models\Website::find($transaction->website_id);
                if ($website) {
                    $website->update(['status' => 'paid']);
                    Log::info('Website automatically activated via payment', ['website_id' => $website->id]);
                }
            }

            Log::info('Cashfree webhook: transaction updated', [
                'transaction_id' => $transaction->id,
                'status'         => $newStatus,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * 4. CUSTOM CHECKOUT PAGE
     *    Displays a branded page to open the Cashfree modal.
     *    GET /pay/{order_id}
     */
    public function showCheckout($cashfreeOrderId)
    {
        $transaction = Transaction::where('cashfree_order_id', $cashfreeOrderId)
            ->with('website')
            ->firstOrFail();

        return view('payment.checkout', [
            'transaction' => $transaction,
            'website'     => $transaction->website,
        ]);
    }
}
