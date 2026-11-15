<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additional show dates for a short-run event (#2375).
 *
 * events.start_at stays the first date; each extra date and time is a row
 * here, so an event with a single date has none. events.last_start_at is the
 * start of the final showing (start_at itself when there are no extra ones),
 * kept on the event so "is this still upcoming" is a plain indexed comparison.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_showings', function (Blueprint $table) {
            $table->id();
            // events.id is an unsigned int
            $table->unsignedInteger('event_id');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->dateTime('door_at')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->index(['event_id', 'start_at']);
            $table->index('start_at');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('last_start_at')->nullable()->after('start_at');
            $table->index('last_start_at');
        });

        DB::table('events')->update(['last_start_at' => DB::raw('start_at')]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['last_start_at']);
            $table->dropColumn('last_start_at');
        });

        Schema::dropIfExists('event_showings');
    }
};
