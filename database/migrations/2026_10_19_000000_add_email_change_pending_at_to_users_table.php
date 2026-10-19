<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email change re-verification (#2321).
 *
 * `email_change_pending_at` is set when a verified user changes their own
 * email, and cleared when the new address is verified. It marks an account
 * that is unverified only because of the change, so the userCleanup command
 * and the admin purge, which delete never-verified signups, leave it alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_change_pending_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_change_pending_at');
        });
    }
};
