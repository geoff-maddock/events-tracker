<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

   /*     Schema::create('object_types', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('table')->nullable();
            $table->timestamps();
        });

        Schema::create('follows', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('object_type')->nullable();
            $table->integer('object_id')->unsigned()->nullable();
            $table->timestamps();
            $table->index(['user_id', 'object_type', 'object_id']);
        });
*/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('object_types');
        Schema::drop('follows');
    }
}
