<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('series', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('slug');
			$table->string('short')->nullable();
			$table->text('description')->nullable();
			$table->integer('visibility_id')->unsigned()->nullable();
			$table->foreign('visibility_id')->references('id')->on('visibility');
			$table->integer('event_type_id')->unsigned()->nullable();
			$table->foreign('event_type_id')->references('id')->on('event_type');
			$table->integer('occurrence_type_id')->unsigned()->nullable();
			$table->foreign('occurrence_type_id')->references('id')->on('occurrence_type');
			$table->integer('occurrence_week_id')->unsigned()->nullable();
			$table->foreign('occurrence_week_id')->references('id')->on('occurrence_week');
			$table->integer('occurrence_day_id')->unsigned()->nullable();
			$table->foreign('occurrence_day_id')->references('id')->on('occurrence_day');
			$table->boolean('hold_date')->default(0);
			$table->timestamps();
			$table->boolean('is_benefit')->default(0);
			$table->integer('promoter_id')->unsigned()->nullable();
			$table->foreign('promoter_id')->references('id')->on('entity');
			$table->integer('venue_id')->unsigned()->nullable();
			$table->foreign('venue_id')->references('id')->on('entity');
			$table->integer('attending')->unsigned()->default(0);
			$table->integer('like')->unsigned()->default(0);
			$table->decimal('presale_price',5,2)->nullable();
			$table->decimal('door_price',5,2)->nullable();
			$table->string('primary_link')->nullable();
			$table->string('ticket_link')->nullable();
			$table->timestamp('founded_at')->nullable();
			$table->timestamp('cancelled_at')->nullable();
			$table->timestamp('soundcheck_at')->nullable();
			$table->timestamp('door_at')->nullable();
			$table->timestamp('start_at');
			$table->timestamp('end_at')->nullable();
			$table->integer('length')->nullable();
			$table->tinyInteger('min_age')->unsigned()->nullable();
			$table->integer('created_by')->default(1);
			$table->integer('updated_by')->nullable();
		});

		Schema::create('entity_series', function(Blueprint $table)
		{
			$table->integer('series_id')->unsigned()->index();
			$table->foreign('series_id')->references('id')->on('series')->onDelete('cascade');
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
		Schema::drop('entity_series');
		Schema::drop('series');
	}

}
