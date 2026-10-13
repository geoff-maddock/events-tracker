<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SignupRiskScorer counts the signups logged from an IP in the last day on
 * every registration (#2312); without this it scans the activity log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['ip_address', 'created_at'], 'activities_ip_address_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_ip_address_created_at_index');
        });
    }
};
