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
        Schema::table('click_tracks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index()->after('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('click_tracks', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
