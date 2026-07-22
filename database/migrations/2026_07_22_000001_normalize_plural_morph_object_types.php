<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The morphMap used to alias Tag as 'tags' and Event as 'events' while every
 * write path stores the singular forms, so no code should ever have written
 * the plural strings. This defensively normalizes any legacy plural rows
 * (e.g. created through the misconfigured morph relations by since-removed
 * code) so they match the corrected singular morphMap. Idempotent; a no-op
 * on clean data.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('follows')->where('object_type', 'tags')->update(['object_type' => 'tag']);
        DB::table('follows')->where('object_type', 'events')->update(['object_type' => 'event']);

        DB::table('likes')->where('object_type', 'tags')->update(['object_type' => 'tag']);
        DB::table('likes')->where('object_type', 'events')->update(['object_type' => 'event']);

        DB::table('comments')->where('commentable_type', 'tags')->update(['commentable_type' => 'tag']);
        DB::table('comments')->where('commentable_type', 'events')->update(['commentable_type' => 'event']);
    }

    public function down(): void
    {
        // Intentionally empty: the singular forms are what the application
        // writes and reads; restoring plural rows would re-break them.
    }
};
