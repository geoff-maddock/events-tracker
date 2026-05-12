<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('social_facebook_accounts');
    }

    public function down(): void
    {
        Schema::create('social_facebook_accounts', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->string('provider_user_id')->nullable();
            $table->string('provider')->nullable();
            $table->timestamps();
        });
    }
};
