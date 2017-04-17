<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Authentication routes...
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');

// Registration routes...
Route::get('auth/register', 'Auth\AuthController@getRegister');
Route::post('auth/register', 'Auth\AuthController@postRegister');

// Password reset link request routes...
Route::get('password/email', 'Auth\PasswordController@getEmail');
Route::post('password/email', 'Auth\PasswordController@postEmail');

// Password reset routes...
Route::get('password/reset/{token}', 'Auth\PasswordController@getReset');
Route::post('password/reset', 'Auth\PasswordController@postReset');

Route::get('/', 'PagesController@home');

Route::get('about', 'PagesController@about');

Route::get('help', 'PagesController@help');

Route::get('calendar', 'EventsController@calendar');

Route::get('search','PagesController@search');

Route::get('activity','PagesController@activity');

Route::bind('users', function($id)
{
	return App\User::whereId($id)->first();
});

$router->resource('users','UsersController');

Route::post('users/{id}/photos', 'UsersController@addPhoto');
Route::delete('users/{id}/photos/{photo_id}', 'UsersController@deletePhoto');

# PHOTOS 

Route::delete('photos/{id}', 'PhotosController@destroy');
Route::post('photos/{id}/setPrimary', 'PhotosController@setPrimary');
Route::post('photos/{id}/unsetPrimary', 'PhotosController@unsetPrimary');

# EVENTS
Route::get('events/all', 'EventsController@indexAll');
Route::get('events/future', 'EventsController@indexFuture');
Route::get('events/past', 'EventsController@indexPast');
Route::get('events/week', 'EventsController@indexWeek');
Route::get('events/starting/{date}', 'EventsController@indexStarting');
Route::get('events/daily', 'EventsController@daily');

Route::get('events/tag/{tag}', 'EventsController@indexTags');
Route::get('events/venue/{slug}', 'EventsController@indexVenues');
Route::get('events/relatedto/{slug}', 'EventsController@indexRelatedTo');
Route::get('events/type/{slug}', 'EventsController@indexTypes');
Route::get('events/series/{slug}', 'EventsController@indexSeries');
Route::get('events/feed', 'EventsController@feed');

Route::get('events/{id}/remind', [
	'as' => 'events.remind', 
	'uses' => 'EventsController@remind'
	]);

Route::get('events/{id}/attending', [
	'as' => 'events.attending', 
	'uses' => 'EventsController@attending'
	]);

Route::get('events/{id}/unattending', [
	'as' => 'events.unattending', 
	'uses' => 'EventsController@unattending'
	]);

Route::post('events/{id}/photos', 'EventsController@addPhoto');
Route::delete('events/{id}/photos/{photo_id}', 'EventsController@deletePhoto');

Route::bind('events', function($id)
{
	return App\Event::whereId($id)->first();
});

$router->resource('events','EventsController');

# THREADS
Route::get('threads/all', 'ThreadsController@indexAll');
Route::get('threads/category/{slug}', 'ThreadsController@indexCategories');
Route::get('threads/tag/{tag}', 'ThreadsController@indexTags');

Route::bind('threads', function($id)
{
	return App\Thread::whereId($id)->first();
});

$router->resource('threads','ThreadsController');

# POSTS
Route::get('posts/all', 'PostsController@indexAll');

Route::bind('posts', function($id)
{
	return App\Post::whereId($id)->first();
});

$router->resource('posts','PostsController');

# ENTITIES
Route::post('entities/{id}/photos', 'EntitiesController@addPhoto');

Route::get('entities/type/{type}', 'EntitiesController@indexTypes');

Route::get('entities/role/{role}', array('as' => 'entities.filter', 'uses' => 'EntitiesController@indexRoles'));

Route::get('entities/filter', array('as' => 'entities.filter', 'uses' => 'EntitiesController@filter'));
Route::get('entities/reset', array('as' => 'entities.reset', 'uses' => 'EntitiesController@indexRoles'));

Route::get('entities/tag/{tag}', 'EntitiesController@indexTags');


Route::get('entities/{id}/follow', [
	'as' => 'entities.follow', 
	'uses' => 'EntitiesController@follow'
	]);

Route::get('entities/{id}/unfollow', [
	'as' => 'entities.unfollow', 
	'uses' => 'EntitiesController@unfollow'
	]);

Route::bind('entities', function($id)
{
	return App\Entity::whereId($id)->first();
});

$router->resource('entities','EntitiesController');

Route::bind('locations', function($id)
{
	return App\Location::whereId($id)->first();
});

$router->resource('entities.locations','LocationsController');

Route::bind('contacts', function($id)
{
	return App\Contact::whereId($id)->first();
});

$router->resource('entities.contacts','ContactsController');

Route::bind('links', function($id)
{
	return App\Link::whereId($id)->first();
});

$router->resource('entities.links','LinksController');

Route::bind('comments', function($id)
{
	return App\Comment::whereId($id)->first();
});

$router->resource('entities.comments','CommentsController');
$router->resource('events.comments','CommentsController');


# SERIES
Route::get('series/createOccurrence', [
	'as' => 'series.createOccurrence', 
	'uses' => 'SeriesController@createOccurrence'
	]);


Route::get('series/type/{type}', 'SeriesController@indexTypes');
Route::get('series/tag/{tag}', 'SeriesController@indexTags');
Route::get('series/relatedto/{slug}', 'SeriesController@indexRelatedTo');
Route::get('series/week', 'SeriesController@indexWeek');
Route::post('series/{id}/photos', 'SeriesController@addPhoto');
Route::delete('series/{id}/photos/{photo_id}', 'SeriesController@deletePhoto');

Route::get('series/{id}/follow', [
	'as' => 'series.follow', 
	'uses' => 'SeriesController@follow'
	]);

Route::get('series/{id}/unfollow', [
	'as' => 'series.unfollow', 
	'uses' => 'SeriesController@unfollow'
	]);

Route::bind('series', function($id)
{
	return App\Series::whereId($id)->first();
});


$router->resource('series','SeriesController');

Route::get('tags/{tag}', 'TagsController@indexTags');
$router->resource('tags','TagsController');

Route::get('tags/{id}/follow', [
	'as' => 'tags.follow', 
	'uses' => 'TagsController@follow'
	]);

Route::get('tags/{id}/unfollow', [
	'as' => 'tags.unfollow', 
	'uses' => 'TagsController@unfollow'
	]);

// Add the new route
get('rss', 'EventsController@rss');