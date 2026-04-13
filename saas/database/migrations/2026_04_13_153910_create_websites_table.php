<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('owner_name')->nullable();
            $table->string('business_name');
            $table->string('category')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('slug')->unique();
            $table->string('url')->nullable();
            $table->foreignId('template_id')->nullable()->constrained()->onDelete('set null');
            $table->json('ai_content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
