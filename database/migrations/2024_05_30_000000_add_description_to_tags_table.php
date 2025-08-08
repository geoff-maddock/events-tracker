<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tags')) {
            return;
        }
        Schema::table('tags', function (Blueprint $table) {
            if (!Schema::hasColumn('tags', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tags')) {
            return;
        }
        Schema::table('tags', function (Blueprint $table) {
            if (Schema::hasColumn('tags', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
