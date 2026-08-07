<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user opt-out for the proactive feedback interview (issue #1998).
 *
 * Defaults to 1 (opted in), matching the polarity of every other setting on
 * this table. Unlike setting_notify_threads_by_follow — which defaults to 0
 * because it sends email — this only gates an in-app prompt that carries a
 * one-click permanent opt-out, so existing profiles are left opted in.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'setting_feedback_requests')) {
                $table->boolean('setting_feedback_requests')
                    ->default(1)
                    ->after('setting_public_profile');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'setting_feedback_requests')) {
                $table->dropColumn('setting_feedback_requests');
            }
        });
    }
};
