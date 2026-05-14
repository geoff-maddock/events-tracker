<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'setting_notify_threads_by_follow')) {
                $table->boolean('setting_notify_threads_by_follow')
                    ->default(0)
                    ->after('setting_forum_update');
            }
        });

        // Per issue #1853, ensure every existing profile is opted out by default.
        DB::table('profiles')->update(['setting_notify_threads_by_follow' => 0]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'setting_notify_threads_by_follow')) {
                $table->dropColumn('setting_notify_threads_by_follow');
            }
        });
    }
};
