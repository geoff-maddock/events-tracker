<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('events', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('slug');
			$table->string('short')->nullable();
			$table->text('description')->nullable();
			$table->integer('visibility_id')->unsigned()->nullable();
			$table->integer('event_status_id')->unsigned()->nullable();
			$table->foreign('event_status_id')->references('id')->on('event_status');
			$table->integer('event_type_id')->references('id')->on('event_type')->nullable();
			$table->boolean('is_benefit')->default(0);
			$table->integer('promoter_id')->unsigned()->nullable();
			$table->foreign('promoter_id')->references('id')->on('entity');
			$table->integer('venue_id')->unsigned()->nullable();
			$table->foreign('venue_id')->references('id')->on('entity');
			$table->integer('attending')->unsigned()->default(0);
			$table->integer('like')->unsigned()->default(0);
			$table->decimal('presale_price',5,2)->nullable();
			$table->decimal('door_price',5,2)->nullable(); 
			$table->timestamp('soundcheck_at')->nullable();
			$table->timestamp('door_at')->nullable();
			$table->timestamp('start_at');
			$table->timestamp('end_at')->nullable();
			$table->tinyInteger('min_age')->unsigned()->nullable();
			$table->integer('series_id')->unsigned()->nullable();
			$table->foreign('series_id')->references('id')->on('series');
			$table->string('primary_link')->nullable();
			$table->string('ticket_link')->nullable();
			$table->integer('created_by')->default(1);
			$table->integer('updated_by')->nullable();
			$table->timestamps();
		});


		Schema::create('response_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');  //  IGNORE, NOT INTERESTED, CANNOT ATTEND, MAY ATTEND, ATTENDING, ATTENDED
			$table->timestamps();
		});

		Schema::create('review_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');  //  INFORMATIONAL, POSITIVE, NEGATIVE, NEUTRAL
			$table->timestamps();
		});

		Schema::create('event_responses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->integer('response_type_id')->unsigned()->index();
			$table->foreign('response_type_id')->references('id')->on('response_type')->onDelete('cascade');
			$table->timestamps();
		});


		Schema::create('event_reviews', function(Blueprint $table)
		{
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->integer('review_type_id')->unsigned()->index();
			$table->foreign('review_type_id')->references('id')->on('review_type')->onDelete('cascade');
			$table->boolean('attended');
			$table->boolean('confirmed')->default(0);
			$table->tinyInteger('expectation'); // 1-5 rating - how well did it meed expectactions 1- far below, 2 below 3 met 4 exceeded 5 far exceeded
			$table->tinyInteger('rating'); // 1-5 rating - how much fun was it? 1- no fun 2 a little fun 3 average enjoyment 4 enjoyble 5 one of the best
			$table->text('review');
			$table->timestamps();
		});


		Schema::create('entity_event', function(Blueprint $table)
		{
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
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
		Schema::drop('response_types');
		Schema::drop('review_types');
		Schema::drop('event_reviews');
		Schema::drop('event_responses');
		Schema::drop('entity_event');
		Schema::drop('events');

	}

}
