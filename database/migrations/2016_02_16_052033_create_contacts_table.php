<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('contacts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 128);
			$table->string('email', 128);
			$table->string('phone', 32);
			$table->string('other', 128);
			$table->string('type', 64);
			$table->integer('visibility_id')->unsigned()->nullable();
			$table->timestamps();
		});

		Schema::create('contact_entity', function(Blueprint $table)
		{
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
			$table->integer('contact_id')->unsigned()->index();
			$table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
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
		Schema::drop('contact_entity');
		Schema::drop('contacts');
	}

}
