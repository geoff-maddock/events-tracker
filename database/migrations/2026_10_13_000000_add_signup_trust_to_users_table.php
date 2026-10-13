<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spam signup defenses (#2312).
 *
 * `trusted_at` is set when an admin approves an account; until an account is
 * trusted (see User::isTrusted()) its bio and social handles are shown only
 * to itself and to admins. `risk_score` and `risk_reasons` hold the signup
 * risk assessment from SignupRiskScorer; null means never assessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trusted_at')->nullable()->after('email_verified_at');
            $table->unsignedSmallInteger('risk_score')->nullable()->after('trusted_at');
            $table->json('risk_reasons')->nullable()->after('risk_score');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trusted_at', 'risk_score', 'risk_reasons']);
        });
    }
};
