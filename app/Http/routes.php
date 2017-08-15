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

Route::get('/', 'PagesController@home')->name('home');
Route::get('/home', 'PagesController@home')->name('home');

Route::get('about', 'PagesController@about');

Route::get('help', 'PagesController@help');

Route::get('calendar', 'EventsController@calendar');
Route::get('calendar/tag/{tag}', 'EventsController@calendarTags')->name('calendar.tag');
Route::get('calendar/relatedto/{slug}', 'EventsController@calendarRelatedTo');
Route::get('calendar/free', 'EventsController@calendarFree')->name('calendar.free');
Route::get('calendar/attending', 'EventsController@calendarAttending')->name('calendar.attending');
Route::get('calendar/type/{tag}', 'EventsController@calendarEventTypes')->name('calendar.type');
Route::get('calendar/min_age/{age}', 'EventsController@calendarMinAge')->name('calendar.minAge');

Route::get('search','PagesController@search');

Route::get('activity','PagesController@activity');

Route::bind('users', function($id)
{
	return App\User::whereId($id)->firstOrFail();
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
Route::get('events/future', 'EventsController@indexFuture')->name('events.future');
Route::get('events/past', 'EventsController@indexPast');
Route::get('events/week', 'EventsController@indexWeek');
Route::get('events/starting/{date}', 'EventsController@indexStarting');
Route::get('events/daily', 'EventsController@daily');

Route::get('events/filter', array('as' => 'events.filter', 'uses' => 'EventsController@filter'));
Route::get('events/reset', array('as' => 'events.reset', 'uses' => 'EventsController@reset'));

Route::get('events/tag/{tag}', 'EventsController@indexTags')->name('events.tag');
Route::get('events/venue/{slug}', 'EventsController@indexVenues')->name('events.venue');
Route::get('events/relatedto/{slug}', 'EventsController@indexRelatedTo');
Route::get('events/type/{slug}', 'EventsController@indexTypes');
Route::get('events/series/{slug}', 'EventsController@indexSeries');
Route::get('events/feed', 'EventsController@feed');
Route::get('events/attending', 'EventsController@indexAttending');

Route::get('events/{id}/remind', [
	'as' => 'events.remind', 
	'uses' => 'EventsController@remind'
	]);

Route::get('events/{id}/attend', [
	'as' => 'events.attend',
	'uses' => 'EventsController@attend'
	]);

Route::get('events/{id}/unattend', [
	'as' => 'events.unattend',
	'uses' => 'EventsController@unattend'
	]);

Route::post('events/{id}/photos', 'EventsController@addPhoto');
Route::delete('events/{id}/photos/{photo_id}', 'EventsController@deletePhoto');

Route::bind('events', function($id)
{
	return App\Event::whereId($id)->firstOrFail();
});

$router->resource('events','EventsController');

# FORUMS
Route::bind('forums', function($id)
{
	return App\Forum::whereId($id)->firstOrFail();
});

Route::get('forums/all', 'ForumsController@indexAll');

$router->resource('forums','ForumsController');

# THREADS
Route::bind('threads', function($id)
{
	return App\Thread::whereId($id)->firstOrFail();
});

Route::get('threads/all', 'ThreadsController@indexAll');
Route::get('threads/category/{slug}', 'ThreadsController@indexCategories');
Route::get('threads/tag/{tag}', 'ThreadsController@indexTags')->name('threads.tag');
Route::get('threads/series/{tag}', 'ThreadsController@indexSeries');
Route::get('threads/relatedto/{slug}', 'ThreadsController@indexRelatedTo');
Route::post('threads/{threads}/posts','PostsController@store');
Route::get('threads/{id}/lock', 'ThreadsController@lock')->name('threads.lock');
Route::get('threads/{id}/unlock', 'ThreadsController@unlock')->name('threads.unlock');


Route::get('threads/{id}/follow', [
    'as' => 'threads.follow',
    'uses' => 'ThreadsController@follow'
]);

Route::get('threads/{id}/unfollow', [
    'as' => 'threads.unfollow',
    'uses' => 'ThreadsController@unfollow'
]);

$router->resource('threads','ThreadsController');

# POSTS
Route::get('posts/all', 'PostsController@indexAll');

Route::bind('posts', function($id)
{
	return App\Post::whereId($id)->firstOrFail();
});

$router->resource('posts','PostsController');

# PERMISSIONS
Route::get('permissions/all', 'PermissionsController@indexAll');

Route::bind('permissions', function($id)
{
	return App\Permission::whereId($id)->firstOrFail();
});

$router->resource('permissions','PermissionsController');


# GROUPS
Route::get('groups/all', 'GroupsController@indexAll');

Route::bind('groups', function($id)
{
	return App\Group::whereId($id)->firstOrFail();
});

$router->resource('groups','GroupsController');


# ENTITIES
Route::post('entities/{id}/photos', 'EntitiesController@addPhoto');

Route::get('entities/type/{type}', 'EntitiesController@indexTypes');

Route::get('entities/role/{role}', 'EntitiesController@indexRoles')->name('entities.role');

Route::get('entities/filter', array('as' => 'entities.filter', 'uses' => 'EntitiesController@filter'));
Route::get('entities/reset', array('as' => 'entities.reset', 'uses' => 'EntitiesController@reset'));

Route::get('entities/tag/{tag}', 'EntitiesController@indexTags')->name('entities.tag');
Route::get('entities/alias/{alias}', 'EntitiesController@indexAliases')->name('entities.tag');


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
	return App\Entity::whereId($id)->firstOrFail();
});

$router->resource('entities','EntitiesController');

Route::bind('locations', function($id)
{
	return App\Location::whereId($id)->firstOrFail();
});

$router->resource('entities.locations','LocationsController');

Route::bind('contacts', function($id)
{
	return App\Contact::whereId($id)->firstOrFail();
});

$router->resource('entities.contacts','ContactsController');

Route::bind('links', function($id)
{
	return App\Link::whereId($id)->firstOrFail();
});

$router->resource('entities.links','LinksController');

Route::bind('comments', function($id)
{
	return App\Comment::whereId($id)->firstOrFail();
});

$router->resource('entities.comments','CommentsController');
$router->resource('events.comments','CommentsController');


# SERIES
Route::get('series/createOccurrence', [
	'as' => 'series.createOccurrence', 
	'uses' => 'SeriesController@createOccurrence'
	]);


Route::get('series/type/{type}', 'SeriesController@indexTypes');
Route::get('series/tag/{tag}', 'SeriesController@indexTags')->name('series.tag');
Route::get('series/relatedto/{slug}', 'SeriesController@indexRelatedTo');
Route::get('series/week', 'SeriesController@indexWeek');
Route::get('series/cancelled', 'SeriesController@indexCancelled')->name('series.cancelled');
Route::post('series/{id}/photos', 'SeriesController@addPhoto');
Route::delete('series/{id}/photos/{photo_id}', 'SeriesController@deletePhoto');

Route::get('series/filter', array('as' => 'series.filter', 'uses' => 'SeriesController@filter'));
Route::get('series/reset', array('as' => 'series.reset', 'uses' => 'SeriesController@reset'));

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
	return App\Series::whereId($id)->firstOrFail();
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

// Add the route for rss
Route::get('rss', 'EventsController@rss');