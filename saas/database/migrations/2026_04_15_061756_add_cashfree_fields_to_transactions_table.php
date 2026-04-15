<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('cashfree_order_id')->nullable()->after('razorpay_payment_id');
            $table->string('cashfree_payment_id')->nullable()->after('cashfree_order_id');
            $table->string('cashfree_payment_status')->nullable()->after('cashfree_payment_id');
            $table->string('plan')->nullable()->after('cashfree_payment_status');       // monthly | yearly | one_time
            $table->string('currency')->default('INR')->after('plan');
            $table->text('cashfree_raw_response')->nullable()->after('currency');       // store full webhook payload
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'cashfree_order_id',
                'cashfree_payment_id',
                'cashfree_payment_status',
                'plan',
                'currency',
                'cashfree_raw_response',
            ]);
        });
    }
};
