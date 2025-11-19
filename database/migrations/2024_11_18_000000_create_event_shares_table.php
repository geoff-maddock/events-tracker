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
        Schema::create('event_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('event_id')->nullable()->index();
            $table->string('platform', 32)->nullable();
            $table->string('platform_id', 64)->nullable();
            $table->integer('created_by')->nullable()->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
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
        Schema::dropIfExists('event_shares');
    }
};
