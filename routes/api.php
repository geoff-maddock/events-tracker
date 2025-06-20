<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::group(['middleware' => ['auth:sanctum']], function () {
    // validate the token
    Route::get('tokens/validate', function () {
        return ['data' => 'token is valid'];
    });

    // invalidate the token
    Route::get('tokens/invalidate', function (Request $request) {
        $request->user()->tokens()->delete();
        return ['data' => 'token invalidated'];
    });

    // check if the user can view a profile
    Route::get('tokens/profile', function (Request $request) {
        if ($request->user()->tokenCan('user:view-profile')) {
            // can view profile
            return ['data' => 'user can view the profile for '. $request->user()->name];
        } else {
            // cannot view profile
            return ['data' => 'user cannot view the profile for '. $request->user()->name];
        }
    });

});

Route::middleware('auth:sanctum')->get('tokens/test', function (Request $request) {
    return ['data' => 'token test'];
});


Route::middleware('auth.basic')->name('api.')->group(function () {

    // creating a token requires basic auth for the user
    Route::post('/tokens/create', function (Request $request) {

        // create the token and add abilities
        $token = $request->user()->createToken($request->token_name, ['user:view-profile','event:show']);
     
        return ['token' => $token->plainTextToken];
    });

});

Route::middleware('auth.either')->name('api.')->group(function () {

    Route::match(['get', 'post'], 'activities/filter', ['as' => 'activities.filter', 'uses' => 'Api\ActivityController@filter']);
    Route::get('activities/reset', ['as' => 'activities.reset', 'uses' => 'Api\ActivityController@reset']);
    Route::get('activities/rpp-reset', ['as' => 'activities.rppReset', 'uses' => 'Api\ActivityController@rppReset']);
    Route::resource('activities', 'Api\ActivityController');

    Route::match(['get', 'post'], 'blogs/filter', ['as' => 'blogs.filter', 'uses' => 'Api\BlogsController@filter']);
    Route::get('blogs/reset', ['as' => 'blogs.reset', 'uses' => 'Api\BlogsController@reset']);
    Route::get('blogs/rpp-reset', ['as' => 'blogs.rppReset', 'uses' => 'Api\BlogsController@rppReset']);
    Route::resource('blogs', 'Api\BlogsController');

    Route::get('events/by-date/{year}/{month?}/{day?}', 'Api\EventsController@indexByDate')
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])')
    ->where('day', '(0?[1-9]|[12][0-9]|3[01])');
    
    Route::get('events/reset', ['as' => 'events.reset', 'uses' => 'Api\EventsController@reset']);
    Route::get('events/rpp-reset', ['as' => 'events.rppReset', 'uses' => 'Api\EventsController@rppReset']);
    
    Route::get('events/{event}/photos', ['as' => 'events.photos', 'uses' => 'Api\EventsController@photos']);
    Route::get('events/{event}/all-photos', ['as' => 'events.allPhotos', 'uses' => 'Api\EventsController@allPhotos']);
    Route::post('events/{id}/photos', 'Api\EventsController@addPhoto');
    Route::get('events/{event}/embeds', ['as' => 'events.embeds', 'uses' => 'Api\EventsController@embeds']);
    Route::get('events/{event}/minimal-embeds', ['as' => 'events.minimalEmbeds', 'uses' => 'Api\EventsController@minimalEmbeds']);
    
    Route::resource('events', 'Api\EventsController');

    Route::get('entities/{entity}/photos', ['as' => 'entities.photos', 'uses' => 'Api\EntitiesController@photos']);
    Route::get('entities/{entity}/embeds', ['as' => 'entities.embeds', 'uses' => 'Api\EntitiesController@embeds']);
    Route::get('entities/{entity}/minimal-embeds', ['as' => 'entities.minimalEmbeds', 'uses' => 'Api\EntitiesController@minimalEmbeds']);
    Route::resource('entities', 'Api\EntitiesController');

    Route::match(['get', 'post'], 'entity-types/filter', ['as' => 'entityType.filter', 'uses' => 'Api\EntityTypesController@filter']);
    Route::get('entity-types/reset', ['as' => 'entity-types.reset', 'uses' => 'Api\EntityTypesController@reset']);
    Route::get('entity-types/rpp-reset', ['as' => 'entity-types.rppReset', 'uses' => 'Api\EntityTypesController@rppReset']);
    Route::resource('entity-types', 'Api\EntityTypesController');

    Route::match(['get', 'post'], 'entity-statuses/filter', ['as' => 'entityStatus.filter', 'uses' => 'Api\EntityStatusesController@filter']);
    Route::get('entity-statuses/reset', ['as' => 'entity-statuses.reset', 'uses' => 'Api\EntityStatusesController@reset']);
    Route::get('entity-statuses/rpp-reset', ['as' => 'entity-statuses.rppReset', 'uses' => 'Api\EntityStatusesController@rppReset']);
    Route::resource('entity-statuses', 'Api\EntityStatusesController');

    Route::match(['get', 'post'], 'event-types/filter', ['as' => 'eventType.filter', 'uses' => 'Api\EventTypesController@filter']);
    Route::get('event-types/reset', ['as' => 'event-types.reset', 'uses' => 'Api\EventTypesController@reset']);
    Route::get('event-types/rpp-reset', ['as' => 'event-types.rppReset', 'uses' => 'Api\EventTypesController@rppReset']);
    Route::resource('event-types', 'Api\EventTypesController');

    Route::match(['get', 'post'], 'forums/filter', ['as' => 'forums.filter', 'uses' => 'Api\ForumsController@filter']);
    Route::get('forums/reset', ['as' => 'forums.reset', 'uses' => 'Api\ForumsController@reset']);
    Route::get('forums/rpp-reset', ['as' => 'forums.rppReset', 'uses' => 'Api\ForumsController@rppReset']);
    Route::resource('forums', 'Api\ForumsController');

    Route::match(['get', 'post'], 'links/filter', ['as' => 'links.filter', 'uses' => 'Api\LinksController@filter']);
    Route::get('links/reset', ['as' => 'links.reset', 'uses' => 'Api\LinksController@reset']);
    Route::get('links/rpp-reset', ['as' => 'links.rppReset', 'uses' => 'Api\LinksController@rppReset']);
    Route::resource('links', 'Api\LinksController');

    Route::match(['get', 'post'], 'locations/filter', ['as' => 'locations.filter', 'uses' => 'Api\LocationsController@filter']);
    Route::get('locations/reset', ['as' => 'locations.reset', 'uses' => 'Api\LocationsController@reset']);
    Route::get('locations/rpp-reset', ['as' => 'locations.rppReset', 'uses' => 'Api\LocationsController@rppReset']);
    Route::resource('locations', 'Api\LocationsController');

    Route::get('series/reset', ['as' => 'series.reset', 'uses' => 'Api\SeriesController@reset']);
    Route::get('series/rpp-reset', ['as' => 'series.rppReset', 'uses' => 'Api\SeriesController@rppReset']);
    Route::resource('series', 'Api\SeriesController');

    Route::match(['get', 'post'], 'tags/filter', ['as' => 'tags.filter', 'uses' => 'Api\TagsController@filter']);
    Route::get('tags/reset', ['as' => 'tags.reset', 'uses' => 'Api\TagsController@reset']);
    Route::get('tags/rpp-reset', ['as' => 'tags.rppReset', 'uses' => 'Api\TagsController@rppReset']);
    Route::resource('tags', 'Api\TagsController');


    Route::match(['get', 'post'], 'posts/filter', ['as' => 'posts.filter', 'uses' => 'Api\PostsController@filter']);
    Route::get('posts/reset', ['as' => 'posts.reset', 'uses' => 'Api\PostsController@reset']);
    Route::get('posts/rpp-reset', ['as' => 'posts.rppReset', 'uses' => 'Api\PostsController@rppReset']);
    Route::resource('posts', 'Api\PostsController');

    Route::match(['get', 'post'], 'threads/filter', ['as' => 'threads.filter', 'uses' => 'Api\ThreadsController@filter']);
    Route::get('threads/reset', ['as' => 'threads.reset', 'uses' => 'Api\ThreadsController@reset']);
    Route::get('threads/rpp-reset', ['as' => 'threads.rppReset', 'uses' => 'Api\ThreadsController@rppReset']);
    Route::get('threads/{threadId}/posts', ['as' => 'threads.posts', 'uses' => 'Api\ThreadsController@posts']);
    Route::resource('threads', 'Api\ThreadsController');

    Route::match(['get', 'post'], 'users/filter', ['as' => 'users.filter', 'uses' => 'Api\UsersController@filter']);
    Route::get('users/reset', ['as' => 'users.reset', 'uses' => 'Api\UsersController@reset']);
    Route::get('users/rpp-reset', ['as' => 'users.rppReset', 'uses' => 'Api\UsersController@rppReset']);
    Route::resource('users', 'Api\UsersController');
});

// routes protected by the shield middleware
Route::middleware('shield')->name('shield.')->group(function () {
});

// calendar routes - these are used by the web app for dynamic loading
Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api');
Route::get('tag-calendar-events', 'EventsController@tagCalendarEventsApi')->name('tagCalendarEvents.api');
