<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks the post-signup "Getting To Know You" onboarding flow (issue #901).
     * A non-null value in either column means the prompt should no longer auto-show:
     * `onboarding_completed_at` when the user followed something through it,
     * `onboarding_dismissed_at` when the user explicitly skipped it.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('setting_public_profile');
            $table->timestamp('onboarding_dismissed_at')->nullable()->after('onboarding_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['onboarding_completed_at', 'onboarding_dismissed_at']);
        });
    }
};
