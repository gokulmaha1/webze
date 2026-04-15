<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cashfree Payment Gateway Configuration
    |--------------------------------------------------------------------------
    | Register at https://merchant.cashfree.com
    | API Docs: https://docs.cashfree.com/reference/pg-new-apis-endpoint
    */

    'app_id'     => env('CASHFREE_APP_ID', ''),
    'secret_key' => env('CASHFREE_SECRET_KEY', ''),

    /**
     * 'production' or 'sandbox'
     * Production: https://api.cashfree.com/pg
     * Sandbox:    https://sandbox.cashfree.com/pg
     */
    'env' => env('CASHFREE_ENV', 'production'),
];
