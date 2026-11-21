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
        // by column, so an index added by hand under another name counts too
        if (Schema::hasIndex('click_tracks', ['clicked_at'])) {
            return;
        }

        Schema::table('click_tracks', function (Blueprint $table) {
            $table->index('clicked_at');
        });
    }

    /**
     * Left in place: the index may be older than this migration (up() does
     * nothing where it already existed), and a rollback must not remove it.
     */
    public function down(): void
    {
    }
};
