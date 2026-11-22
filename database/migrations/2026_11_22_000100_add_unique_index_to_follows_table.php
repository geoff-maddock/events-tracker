<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One follow per user per thing, as for likes. Extra rows go first (the
     * earliest is kept), then the plain index on the same columns becomes unique.
     */
    public function up(): void
    {
        DB::statement(
            'DELETE f FROM follows f JOIN follows k'
            .' ON k.user_id = f.user_id AND k.object_type = f.object_type AND k.object_id = f.object_id AND k.id < f.id'
        );

        if (Schema::hasIndex('follows', ['user_id', 'object_type', 'object_id'], 'unique')) {
            return;
        }

        Schema::table('follows', function (Blueprint $table) {
            $table->unique(['user_id', 'object_type', 'object_id'], 'follows_user_object_unique');
        });

        // the unique index serves the same lookups
        if (Schema::hasIndex('follows', 'follows_user_id_object_type_object_id_index')) {
            Schema::table('follows', function (Blueprint $table) {
                $table->dropIndex('follows_user_id_object_type_object_id_index');
            });
        }
    }

    /**
     * The removed duplicates are not restored.
     */
    public function down(): void
    {
        if (!Schema::hasIndex('follows', 'follows_user_object_unique')) {
            return;
        }

        Schema::table('follows', function (Blueprint $table) {
            $table->index(['user_id', 'object_type', 'object_id']);
            $table->dropUnique('follows_user_object_unique');
        });
    }
};
