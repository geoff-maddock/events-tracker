<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Engagement gate on the daily and weekly digests (#2083).
 *
 * A user with no activity in 90 days stops getting digests and is told once.
 *  - digests_paused_at: the paused notice went out, so it isn't sent again.
 *  - digests_confirmed_at: the user said "keep sending" (the resume link, or
 *    saving the preference page with a digest on). It doesn't expire, so a
 *    reader who never logs in is asked once, not every 90 days.
 *  - email_clicked_at: the last click on a site link in one of their emails,
 *    which counts as activity like a login does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->timestamp('digests_paused_at')->nullable();
            $table->timestamp('digests_confirmed_at')->nullable();
            $table->timestamp('email_clicked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['digests_paused_at', 'digests_confirmed_at', 'email_clicked_at']);
        });
    }
};
