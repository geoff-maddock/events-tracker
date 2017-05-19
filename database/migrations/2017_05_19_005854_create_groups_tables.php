<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('label');
            $table->string('description');
            $table->integer('level');
            $table->timestamps();
        });

        Schema::create('permissions', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('label');
            $table->string('description');
            $table->integer('level');
            $table->timestamps();
        });

        Schema::create('group_permission', function(Blueprint $table) {
            $table->integer('group_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->primary(['group_id', 'permission_id']);
  /*          $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');
    */
        });

        Schema::create('group_user', function(Blueprint $table) {
            $table->integer('group_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->primary(['group_id', 'user_id']);
    /*
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
                */
        });

        Schema::create('access_types', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('label');
            $table->string('description');
            $table->integer('level');
            $table->timestamps();
        });

        Schema::create('object_types', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('label');
            $table->string('description');
            $table->integer('level');
            $table->boolean('is_active');
            $table->timestamps();
        });

        Schema::create('access', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('object_id')->unsigned();
            $table->integer('object_type_id')->unsigned();
            $table->integer('access_type_id')->unsigned();
            $table->string('label');
            $table->integer('level');
            $table->boolean('can_grant');
            $table->timestamps();
        /*
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('object_type_id')
                ->references('id')
                ->on('object_types')
                ->onDelete('cascade');
            $table->foreign('access_type')
                ->references('id')
                ->on('access_types')
                ->onDelete('cascade');
                */
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('permissions');
        Schema::drop('groups');
        Schema::drop('object_types');
        Schema::drop('access_types');
        Schema::drop('access');
    }
}
