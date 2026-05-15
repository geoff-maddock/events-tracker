<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('query', 191);
            $table->unsignedInteger('results_count')->default(0);
            $table->string('source', 16)->default('web');   // web | api
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            // No updated_at — log rows are write-once.
        });

        // Useful for "top queries" reports.
        Schema::table('search_logs', function (Blueprint $table) {
            $table->index('query');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
