<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tags', 'created_by')) {
            return;
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedInteger('created_by')->nullable()->after('tag_type_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tags', 'created_by')) {
            return;
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
