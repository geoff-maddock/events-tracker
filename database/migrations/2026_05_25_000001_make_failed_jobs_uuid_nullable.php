<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The framework's DatabaseFailedJobProvider::log() does not insert a uuid,
 * so the NOT NULL `uuid` column from the original migration caused every
 * failed-job insert to error out with "Field 'uuid' doesn't have a default value".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('failed_jobs') && Schema::hasColumn('failed_jobs', 'uuid')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->string('uuid')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('failed_jobs') && Schema::hasColumn('failed_jobs', 'uuid')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->string('uuid')->nullable(false)->change();
            });
        }
    }
};
