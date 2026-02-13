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
        Schema::create('click_tracks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('event_id')->nullable()->index();
            $table->unsignedInteger('venue_id')->nullable()->index();
            $table->unsignedInteger('promoter_id')->nullable()->index();
            $table->string('tags')->nullable(); // comma-separated list of tags
            $table->string('user_agent', 512)->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 max length
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();
            
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_tracks');
    }
};
