<?php
2: 
3: use Illuminate\Database\Migrations\Migration;
4: use Illuminate\Database\Schema\Blueprint;
5: use Illuminate\Support\Facades\Schema;
6: 
7: return new class extends Migration
8: {
9:     public function up(): void
10:     {
11:         Schema::table('transactions', function (Blueprint $table) {
12:             $table->string('cashfree_link_id')->nullable()->after('cashfree_raw_response');
13:             $table->string('cashfree_link_url')->nullable()->after('cashfree_link_id');
14:         });
15:     }
16: 
17:     public function down(): void
18:     {
19:         Schema::table('transactions', function (Blueprint $table) {
20:             $table->dropColumn(['cashfree_link_id', 'cashfree_link_url']);
21:         });
22:     }
23: };
24: 
