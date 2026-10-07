<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Engagement gate on the daily and weekly digests (#2083).
 *
 * A user with no activity in 90 days stops getting digests and is told once.
 * digests_paused_at records that the notice went out, so it isn't sent again;
 * digests_resumed_at is set by the resume link in that notice and counts as
 * activity, so a resumed user gets another 90 days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->timestamp('digests_paused_at')->nullable();
            $table->timestamp('digests_resumed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['digests_paused_at', 'digests_resumed_at']);
        });
    }
};
