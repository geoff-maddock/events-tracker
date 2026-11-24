<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nothing stopped the same response being stored twice: two "attend" requests
 * arriving together both found no row and both added one. Each extra row
 * counted as one more person attending. The repeats go (the earliest is kept)
 * and the three columns become unique.
 */
return new class extends Migration
{
    private const COLUMNS = ['event_id', 'user_id', 'response_type_id'];

    public function up(): void
    {
        if (Schema::hasIndex('event_responses', self::COLUMNS, 'unique')) {
            return;
        }

        DB::statement(
            'DELETE later FROM event_responses later
             INNER JOIN event_responses earlier
                ON earlier.event_id = later.event_id
               AND earlier.user_id = later.user_id
               AND earlier.response_type_id = later.response_type_id
               AND earlier.id < later.id'
        );

        Schema::table('event_responses', function (Blueprint $table) {
            $table->unique(self::COLUMNS);
        });
    }

    /**
     * The removed repeats are not restored.
     */
    public function down(): void
    {
        // by name: a unique index that was already there under another name is not this migration's
        if (Schema::hasIndex('event_responses', 'event_responses_event_id_user_id_response_type_id_unique')) {
            Schema::table('event_responses', function (Blueprint $table) {
                $table->dropUnique(self::COLUMNS);
            });
        }
    }
};
