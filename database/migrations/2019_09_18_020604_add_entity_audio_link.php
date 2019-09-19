<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEntityAudioLink extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('entities','soundcloud_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->string('soundcloud_username', 64);
            });
        }

        if (!Schema::hasColumn('entities','spotify_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->string('spotify_username', 64);
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
        if (Schema::hasColumn('entities','soundcloud_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->dropColumn('soundcloud_username');
            });
        }

        if (Schema::hasColumn('entities','spotify_username')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->dropColumn('spotify_username');
            });
        }
    }
}
