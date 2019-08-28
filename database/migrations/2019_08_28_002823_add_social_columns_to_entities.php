<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialColumnsToEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('entities','facebook_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->string('facebook_username', 64);
            });
        }

        if (!Schema::hasColumn('entities','twitter_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->string('twitter_username', 64);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('entities','facebook_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->dropColumn('facebook_username');
            });
        }

        if (Schema::hasColumn('entities','twitter_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->dropColumn('twitter_username');
            });
        }
    }
}
