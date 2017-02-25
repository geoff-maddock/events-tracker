<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('actions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->string('object_table')->nullable();
            $table->string('child_object_table')->nullable();
            $table->text('description')->nullable();
            $table->text('changes')->nullable();
            $table->integer('order');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('activities', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('object_table')->nullable();
            $table->string('object_name')->nullable();
            $table->integer('object_id');
            $table->string('child_object_table')->nullable();
            $table->string('child_object_name')->nullable();
            $table->integer('child_object_id')->nullable();
            $table->text('message')->nullable();
            $table->text('changes')->nullable();
            $table->integer('action_id')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activities');
        Schema::drop('actions');
    }
}
