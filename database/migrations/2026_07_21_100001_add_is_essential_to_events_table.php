<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'is_essential')) {
                $table->boolean('is_essential')
                    ->default(0)
                    ->after('do_not_repost');
            }
            if (!Schema::hasColumn('events', 'essential_note')) {
                $table->string('essential_note')
                    ->nullable()
                    ->after('is_essential');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'essential_note')) {
                $table->dropColumn('essential_note');
            }
            if (Schema::hasColumn('events', 'is_essential')) {
                $table->dropColumn('is_essential');
            }
        });
    }
};
