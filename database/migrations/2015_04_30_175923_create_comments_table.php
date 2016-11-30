<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		Schema::create('comments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('message');
			$table->integer('response_to')->unsigned()->nullable();
			$table->foreign('response_to')->references('id')->on('comment');
			$table->integer('commentable_id')->unsigned()->nullable();
			$table->string('commentable_type')->nullable();  // event, entity 
			$table->integer('created_by')->default(1);
			$table->integer('updated_by')->nullable();
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
		Schema::drop('comments');

	}

}
