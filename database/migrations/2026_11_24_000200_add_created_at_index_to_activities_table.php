<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The activity log is listed newest first and the activity graph and the
     * admin summary select a date range (#2413); the only index holding
     * created_at started with ip_address, so each of them read the whole table.
     */
    public function up(): void
    {
        // by column, so an index added by hand under another name counts too
        if (Schema::hasIndex('activities', ['created_at'])) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('activities', ['created_at'])) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropIndex(['created_at']);
            });
        }
    }
};
