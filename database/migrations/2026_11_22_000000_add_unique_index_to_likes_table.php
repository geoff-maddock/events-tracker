<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One like per user per thing. Repeated like requests used to add a row each
     * time, so the extra rows go first (the earliest is kept), then the `likes`
     * counters on threads and posts are set from the rows that remain.
     */
    public function up(): void
    {
        DB::statement(
            'DELETE l FROM likes l JOIN likes k'
            .' ON k.user_id = l.user_id AND k.object_type = l.object_type AND k.object_id = l.object_id AND k.id < l.id'
        );

        if (!Schema::hasIndex('likes', ['user_id', 'object_type', 'object_id'], 'unique')) {
            Schema::table('likes', function (Blueprint $table) {
                $table->unique(['user_id', 'object_type', 'object_id'], 'likes_user_object_unique');
            });
        }

        foreach (['threads' => 'thread', 'posts' => 'post'] as $table => $type) {
            DB::update(
                // updated_at is named so a recount does not make old threads look freshly edited
                "UPDATE {$table} t SET t.updated_at = t.updated_at, t.likes = (SELECT COUNT(*) FROM likes l WHERE l.object_type = ? AND l.object_id = t.id)"
                ." WHERE t.likes <> (SELECT COUNT(*) FROM likes l WHERE l.object_type = ? AND l.object_id = t.id)",
                [$type, $type]
            );
        }
    }

    /**
     * The removed duplicates and the old counter values are not restored.
     */
    public function down(): void
    {
        if (Schema::hasIndex('likes', ['user_id', 'object_type', 'object_id'], 'unique')) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropUnique('likes_user_object_unique');
            });
        }
    }
};
