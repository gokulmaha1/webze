<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('cashfree_link_id')->nullable()->after('cashfree_raw_response');
            $table->string('cashfree_link_url')->nullable()->after('cashfree_link_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['cashfree_link_id', 'cashfree_link_url']);
        });
    }
};
