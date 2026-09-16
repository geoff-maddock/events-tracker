<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily rollups behind the entity owner dashboard (#2111, #2146).
 *
 * entity_stats_daily.views is incremented live from the entity page, because
 * page views are not recorded anywhere else and cannot be backfilled. The
 * other counters are rebuilt by `entities:rollup-stats` from follows,
 * click_tracks and event_responses.
 *
 * event_reach_daily counts how many recipients an event reached per channel;
 * the weekly digest is the only channel recorded here so far, because
 * Instagram (event_shares) and Discord (discord_posts) already log per event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entity_id');
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('follows')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('responses')->default(0);
            $table->timestamps();

            $table->unique(['entity_id', 'date']);
            $table->index('date');
            $table->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();
        });

        Schema::create('event_reach_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('event_id');
            $table->date('date');
            // digest for now; kept open for other recipient-counted channels
            $table->string('channel', 16);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'date', 'channel']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reach_daily');
        Schema::dropIfExists('entity_stats_daily');
    }
};
