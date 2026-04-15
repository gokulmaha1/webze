<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_logs', function (Blueprint $table) {
            $table->string('page_url')->nullable()->after('website_id');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_logs', function (Blueprint $table) {
            $table->dropColumn('page_url');
        });
    }
};
