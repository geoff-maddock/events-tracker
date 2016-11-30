<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('permissions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('slug');
			$table->string('description');
			$table->timestamps();
		});

		Schema::create('event_permission', function(Blueprint $table)
		{
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('permission_id')->unsigned()->index();
			$table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->timestamps();
		});

		Schema::create('entity_permission', function(Blueprint $table)
		{
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
			$table->integer('permission_id')->unsigned()->index();
			$table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->timestamps();
		});

		Schema::create('event_type_permission', function(Blueprint $table)
		{
			$table->integer('event_type_id')->unsigned()->index();
			$table->foreign('event_type_id')->references('id')->on('event_types')->onDelete('cascade');
			$table->integer('permission_id')->unsigned()->index();
			$table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
		Schema::drop('event_permission');
		Schema::drop('entity_permission');
		Schema::drop('event_type_permission');
		Schema::drop('permissions');
	}

}
