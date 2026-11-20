<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The stats queries and clicks:prune both select by clicked_at. Production
     * already has this index, added by hand; a fresh install did not.
     */
    public function up(): void
    {
        if (Schema::hasIndex('click_tracks', 'click_tracks_clicked_at_index')) {
            return;
        }

        Schema::table('click_tracks', function (Blueprint $table) {
            $table->index('clicked_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasIndex('click_tracks', 'click_tracks_clicked_at_index')) {
            return;
        }

        Schema::table('click_tracks', function (Blueprint $table) {
            $table->dropIndex(['clicked_at']);
        });
    }
};
