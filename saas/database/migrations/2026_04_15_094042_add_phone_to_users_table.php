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
11:         Schema::table('users', function (Blueprint $table) {
12:             $table->string('phone')->nullable()->after('email');
13:         });
14:     }
15: 
16:     public function down(): void
17:     {
18:         Schema::table('users', function (Blueprint $table) {
19:             $table->dropColumn('phone');
20:         });
21:     }
22: };
23: 
