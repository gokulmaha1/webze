<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - Webze</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Cashfree SDK -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        :root {
            --primary: #fbbf24;
            --bg: #0f172a;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(251, 191, 36, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(99, 102, 241, 0.08) 0%, transparent 40%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }

        .container {
            max-width: 450px;
            width: 90%;
            padding: 2.5rem;
            background: var(--glass);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 3px solid var(--glass-border);
            border-bottom-color: var(--primary);
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
            margin-bottom: 2rem;
        }

        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        p {
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        .order-summary {
            background: rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
            border: 1px solid var(--glass-border);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .pay-btn {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .pay-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(251, 191, 36, 0.2);
        }

        .footer-text {
            font-size: 0.8rem;
            opacity: 0.5;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
            Webze
        </div>

        <span class="loader" id="loader"></span>
        
        <div id="content">
            <h1>Complete Payment</h1>
            <p>Ready to activate <b>{{ $website->business_name }}</b></p>
            
            <div class="order-summary">
                <div class="summary-item">
                    <span>Activation Fee</span>
                    <span>₹{{ number_format($transaction->amount, 2) }}</span>
                </div>
                <div class="summary-item" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem; margin-top: 0.5rem; font-weight: 600;">
                    <span>Total</span>
                    <span style="color: var(--primary);">₹{{ number_format($transaction->amount, 2) }}</span>
                </div>
            </div>

            <button onclick="pay()" class="pay-btn" id="pay-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Secure Checkout
            </button>
        </div>

        <p class="footer-text">Protected by Cashfree Secure Payments</p>
    </div>

    <script>
        const cashfree = Cashfree({
            mode: "{{ config('cashfree.env', 'production') === 'production' ? 'production' : 'sandbox' }}"
        });

        function pay() {
            let checkoutOptions = {
                paymentSessionId: "{{ $transaction->cashfree_payment_session_id }}",
                redirectTarget: "_self", // or "_blank" or "_modal" (SDK handled)
            };
            cashfree.checkout(checkoutOptions);
        }

        // Auto-open on load for better UX
        window.onload = function() {
            // Optional delay for aesthetic
            setTimeout(() => {
                pay();
            }, 1500);
        };
    </script>
</body>
</html>
