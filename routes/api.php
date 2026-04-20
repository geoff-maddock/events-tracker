<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Resources\AuthUserResource;

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

Route::middleware('auth:sanctum')->get('auth/me', function (Request $request) {
    // $user = $request->user()->load(['groups.permissions']);
    // return new AuthUserResource($user);
    return response()->json(new AuthUserResource($request->user()));
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
    Route::put('blogs/{blog}', 'Api\BlogsController@update')->name('blogs.update');
    Route::patch('blogs/{blog}', 'Api\BlogsController@patch')->name('blogs.patch');
    Route::resource('blogs', 'Api\BlogsController')->except(['update']);

    Route::get('events/attending', ['as' => 'events.attending', 'uses' => 'Api\EventsController@indexAttending']);
    Route::get('events/recommended', ['as' => 'events.recommended', 'uses' => 'Api\EventsController@indexRecommended'])->middleware('auth:sanctum');
    Route::get('events/popular', ['as' => 'events.popular', 'uses' => 'Api\EventsController@popular']);
    Route::get('events/by-date/{year}/{month?}/{day?}', 'Api\EventsController@indexByDate')
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])')
    ->where('day', '(0?[1-9]|[12][0-9]|3[01])');
    
    Route::get('events/reset', ['as' => 'events.reset', 'uses' => 'Api\EventsController@reset']);
    Route::get('events/rpp-reset', ['as' => 'events.rppReset', 'uses' => 'Api\EventsController@rppReset']);
    
    Route::get('events/{event}/photos', ['as' => 'events.photos', 'uses' => 'Api\EventsController@photos']);
    Route::get('events/{event}/all-photos', ['as' => 'events.allPhotos', 'uses' => 'Api\EventsController@allPhotos']);
    Route::post('events/{id}/photos', 'Api\EventsController@addPhoto');
    Route::post('events/{id}/instagram-post', 'Api\\EventInstagramController@postCarouselToInstagramApi');
    Route::get('events/{event}/embeds', ['as' => 'events.embeds', 'uses' => 'Api\EventsController@embeds']);
    Route::get('events/{event}/minimal-embeds', ['as' => 'events.minimalEmbeds', 'uses' => 'Api\EventsController@minimalEmbeds']);

    Route::post('events/{event}/attend', 'Api\EventsController@attendJson');
    Route::delete('events/{event}/attend', 'Api\EventsController@unattendJson');

    Route::put('events/{event}', 'Api\EventsController@update')->name('events.update');
    Route::patch('events/{event}', 'Api\EventsController@patch')->name('events.patch');
    Route::resource('events', 'Api\EventsController')->except(['update']);

    Route::get('entities/{entity}/photos', ['as' => 'entities.photos', 'uses' => 'Api\EntitiesController@photos']);
    Route::get('entities/{entity}/links', ['as' => 'entities.links', 'uses' => 'Api\EntitiesController@links']);
    Route::get('entities/{entity}/locations', ['as' => 'entities.locations', 'uses' => 'Api\EntitiesController@locations']);
    Route::get('entities/{entity}/contacts', ['as' => 'entities.contacts', 'uses' => 'Api\EntitiesController@contacts']);
    Route::get('entities/{entity}/embeds', ['as' => 'entities.embeds', 'uses' => 'Api\EntitiesController@embeds']);
    Route::get('entities/{entity}/minimal-embeds', ['as' => 'entities.minimalEmbeds', 'uses' => 'Api\EntitiesController@minimalEmbeds']);
    Route::post('entities/{id}/photos', 'Api\EntitiesController@addPhoto');
    Route::post('entities/{id}/links', 'Api\EntitiesController@addLink');
    Route::post('entities/{id}/locations', 'Api\EntitiesController@addLocation');
    Route::post('entities/{id}/contacts', 'Api\EntitiesController@addContact');
    Route::put('entities/{id}/links/{linkId}', 'Api\EntitiesController@updateLink');
    Route::patch('entities/{id}/links/{linkId}', 'Api\EntitiesController@patchLink');
    Route::put('entities/{id}/locations/{locationId}', 'Api\EntitiesController@updateLocation');
    Route::patch('entities/{id}/locations/{locationId}', 'Api\EntitiesController@patchLocation');
    Route::put('entities/{id}/contacts/{contactId}', 'Api\EntitiesController@updateContact');
    Route::patch('entities/{id}/contacts/{contactId}', 'Api\EntitiesController@patchContact');
    Route::delete('entities/{id}/links/{linkId}', 'Api\EntitiesController@deleteLink');
    Route::delete('entities/{id}/locations/{locationId}', 'Api\EntitiesController@deleteLocation');
    Route::delete('entities/{id}/contacts/{contactId}', 'Api\EntitiesController@deleteContact');
    Route::post('entities/{entity}/follow', 'Api\EntitiesController@followJson')->middleware('auth:sanctum');
    Route::post('entities/{entity}/unfollow', 'Api\EntitiesController@unfollowJson')->middleware('auth:sanctum');
    Route::get('entities/popular', ['as' => 'entities.popular', 'uses' => 'Api\EntitiesController@popular']);
    Route::get('entities/following', ['as' => 'entities.following', 'uses' => 'Api\EntitiesController@indexFollowingJson'])->middleware('auth:sanctum');
    // PUT replaces the resource; PATCH applies a partial update. Both are
    // wired explicitly so they map to distinct controller methods rather than
    // the single update() endpoint Route::resource would otherwise generate.
    Route::put('entities/{entity}', 'Api\EntitiesController@update')->name('entities.update');
    Route::patch('entities/{entity}', 'Api\EntitiesController@patch')->name('entities.patch');
    Route::resource('entities', 'Api\EntitiesController')->except(['update']);

    Route::match(['get', 'post'], 'entity-types/filter', ['as' => 'entityType.filter', 'uses' => 'Api\EntityTypesController@filter']);
    Route::get('entity-types/reset', ['as' => 'entity-types.reset', 'uses' => 'Api\EntityTypesController@reset']);
    Route::get('entity-types/rpp-reset', ['as' => 'entity-types.rppReset', 'uses' => 'Api\EntityTypesController@rppReset']);
    Route::put('entity-types/{entity_type}', 'Api\EntityTypesController@update')->name('entity-types.update');
    Route::patch('entity-types/{entity_type}', 'Api\EntityTypesController@patch')->name('entity-types.patch');
    Route::resource('entity-types', 'Api\EntityTypesController')->except(['update']);

    Route::match(['get', 'post'], 'entity-statuses/filter', ['as' => 'entityStatus.filter', 'uses' => 'Api\EntityStatusesController@filter']);
    Route::get('entity-statuses/reset', ['as' => 'entity-statuses.reset', 'uses' => 'Api\EntityStatusesController@reset']);
    Route::get('entity-statuses/rpp-reset', ['as' => 'entity-statuses.rppReset', 'uses' => 'Api\EntityStatusesController@rppReset']);
    Route::put('entity-statuses/{entity_status}', 'Api\EntityStatusesController@update')->name('entity-statuses.update');
    Route::patch('entity-statuses/{entity_status}', 'Api\EntityStatusesController@patch')->name('entity-statuses.patch');
    Route::resource('entity-statuses', 'Api\EntityStatusesController')->except(['update']);

    Route::match(['get', 'post'], 'event-types/filter', ['as' => 'eventType.filter', 'uses' => 'Api\EventTypesController@filter']);
    Route::get('event-types/reset', ['as' => 'event-types.reset', 'uses' => 'Api\EventTypesController@reset']);
    Route::get('event-types/rpp-reset', ['as' => 'event-types.rppReset', 'uses' => 'Api\EventTypesController@rppReset']);
    Route::put('event-types/{event_type}', 'Api\EventTypesController@update')->name('event-types.update');
    Route::patch('event-types/{event_type}', 'Api\EventTypesController@patch')->name('event-types.patch');
    Route::resource('event-types', 'Api\EventTypesController')->except(['update']);

    Route::match(['get', 'post'], 'event-statuses/filter', ['as' => 'eventStatus.filter', 'uses' => 'Api\EventStatusesController@filter']);
    Route::get('event-statuses/reset', ['as' => 'event-statuses.reset', 'uses' => 'Api\EventStatusesController@reset']);
    Route::get('event-statuses/rpp-reset', ['as' => 'event-statuses.rppReset', 'uses' => 'Api\EventStatusesController@rppReset']);
    Route::put('event-statuses/{event_status}', 'Api\EventStatusesController@update')->name('event-statuses.update');
    Route::patch('event-statuses/{event_status}', 'Api\EventStatusesController@patch')->name('event-statuses.patch');
    Route::resource('event-statuses', 'Api\EventStatusesController')->except(['update']);

    Route::match(['get', 'post'], 'forums/filter', ['as' => 'forums.filter', 'uses' => 'Api\ForumsController@filter']);
    Route::get('forums/reset', ['as' => 'forums.reset', 'uses' => 'Api\ForumsController@reset']);
    Route::get('forums/rpp-reset', ['as' => 'forums.rppReset', 'uses' => 'Api\ForumsController@rppReset']);
    Route::put('forums/{forum}', 'Api\ForumsController@update')->name('forums.update');
    Route::patch('forums/{forum}', 'Api\ForumsController@patch')->name('forums.patch');
    Route::resource('forums', 'Api\ForumsController')->except(['update']);

    Route::match(['get', 'post'], 'links/filter', ['as' => 'links.filter', 'uses' => 'Api\LinksController@filter']);
    Route::get('links/reset', ['as' => 'links.reset', 'uses' => 'Api\LinksController@reset']);
    Route::get('links/rpp-reset', ['as' => 'links.rppReset', 'uses' => 'Api\LinksController@rppReset']);
    Route::resource('links', 'Api\LinksController');
    Route::put('menus/{menu}', 'Api\MenusController@update')->name('menus.update');
    Route::patch('menus/{menu}', 'Api\MenusController@patch')->name('menus.patch');
    Route::resource('menus', 'Api\MenusController')->except(['update']);

    Route::match(['get', 'post'], 'locations/filter', ['as' => 'locations.filter', 'uses' => 'Api\LocationsController@filter']);
    Route::get('locations/reset', ['as' => 'locations.reset', 'uses' => 'Api\LocationsController@reset']);
    Route::get('locations/rpp-reset', ['as' => 'locations.rppReset', 'uses' => 'Api\LocationsController@rppReset']);
    Route::put('locations/{location}', 'Api\LocationsController@update')->name('locations.update');
    Route::patch('locations/{location}', 'Api\LocationsController@patch')->name('locations.patch');
    Route::resource('locations', 'Api\LocationsController')->except(['update']);

    Route::get('series/reset', ['as' => 'series.reset', 'uses' => 'Api\SeriesController@reset']);
    Route::get('series/rpp-reset', ['as' => 'series.rppReset', 'uses' => 'Api\SeriesController@rppReset']);
    Route::get('series/{series}/photos', ['as' => 'series.photos', 'uses' => 'Api\SeriesController@photos']);
    Route::get('series/{series}/all-photos', ['as' => 'series.allPhotos', 'uses' => 'Api\SeriesController@allPhotos']);
    Route::post('series/{id}/photos', 'Api\SeriesController@addPhoto');
    Route::post('series/{series}/follow', 'Api\SeriesController@followJson')->middleware('auth:sanctum');
    Route::post('series/{series}/unfollow', 'Api\SeriesController@unfollowJson')->middleware('auth:sanctum');
    Route::get('series/popular', ['as' => 'series.popular', 'uses' => 'Api\SeriesController@popular']);
    Route::put('series/{series}', 'Api\SeriesController@update')->name('series.update');
    Route::patch('series/{series}', 'Api\SeriesController@patch')->name('series.patch');
    Route::resource('series', 'Api\SeriesController')->except(['update']);

    Route::match(['get', 'post'], 'tags/filter', ['as' => 'tags.filter', 'uses' => 'Api\TagsController@filter']);
    Route::get('tags/reset', ['as' => 'tags.reset', 'uses' => 'Api\TagsController@reset']);
    Route::get('tags/rpp-reset', ['as' => 'tags.rppReset', 'uses' => 'Api\TagsController@rppReset']);
    Route::post('tags/{tag}/follow', 'Api\TagsController@followJson')->middleware('auth:sanctum');
    Route::post('tags/{tag}/unfollow', 'Api\TagsController@unfollowJson')->middleware('auth:sanctum');
    Route::delete('tags/{tag}', 'Api\TagsController@destroy');
    Route::get('tags/{tag}/related-tags', ['as' => 'tags.relatedTags', 'uses' => 'Api\TagsController@relatedTags']);
    Route::get('tags/popular', ['as' => 'tags.popular', 'uses' => 'Api\TagsController@popular']);
    Route::resource('tags', 'Api\TagsController')->except(['destroy']);
    Route::match(['get', 'post'], 'tag-types/filter', ['as' => 'tag-types.filter', 'uses' => 'Api\TagTypesController@filter']);
    Route::resource('tag-types', 'Api\TagTypesController')->only(['index', 'show']);


    Route::match(['get', 'post'], 'roles/filter', ['as' => 'roles.filter', 'uses' => 'Api\RolesController@filter']);
    Route::get('roles/reset', ['as' => 'roles.reset', 'uses' => 'Api\RolesController@reset']);
    Route::get('roles/rpp-reset', ['as' => 'roles.rppReset', 'uses' => 'Api\RolesController@rppReset']);
    Route::put('roles/{role}', 'Api\RolesController@update')->name('roles.update');
    Route::patch('roles/{role}', 'Api\RolesController@patch')->name('roles.patch');
    Route::resource('roles', 'Api\RolesController')->except(['update']);


    Route::match(['get', 'post'], 'posts/filter', ['as' => 'posts.filter', 'uses' => 'Api\PostsController@filter']);
    Route::get('posts/reset', ['as' => 'posts.reset', 'uses' => 'Api\PostsController@reset']);
    Route::get('posts/rpp-reset', ['as' => 'posts.rppReset', 'uses' => 'Api\PostsController@rppReset']);
    Route::put('posts/{post}', 'Api\PostsController@update')->name('posts.update');
    Route::patch('posts/{post}', 'Api\PostsController@patch')->name('posts.patch');
    Route::resource('posts', 'Api\PostsController')->except(['update']);

    Route::match(['get', 'post'], 'threads/filter', ['as' => 'threads.filter', 'uses' => 'Api\ThreadsController@filter']);
    Route::get('threads/reset', ['as' => 'threads.reset', 'uses' => 'Api\ThreadsController@reset']);
    Route::get('threads/rpp-reset', ['as' => 'threads.rppReset', 'uses' => 'Api\ThreadsController@rppReset']);
    Route::get('threads/{threadId}/posts', ['as' => 'threads.posts', 'uses' => 'Api\ThreadsController@posts']);
    Route::put('threads/{thread}', 'Api\ThreadsController@update')->name('threads.update');
    Route::patch('threads/{thread}', 'Api\ThreadsController@patch')->name('threads.patch');
    Route::resource('threads', 'Api\ThreadsController')->except(['update']);

    Route::match(['get', 'post'], 'users/filter', ['as' => 'users.filter', 'uses' => 'Api\UsersController@filter']);
    Route::get('users/reset', ['as' => 'users.reset', 'uses' => 'Api\UsersController@reset']);
    Route::get('users/rpp-reset', ['as' => 'users.rppReset', 'uses' => 'Api\UsersController@rppReset']);
    Route::get('users/{user}/events-attending', ['as' => 'users.events-attending', 'uses' => 'Api\UsersController@eventsAttending']);
    Route::resource('users', 'Api\UsersController');

    Route::match(['get', 'post'], 'visibilities/filter', ['as' => 'visibilities.filter', 'uses' => 'Api\VisibilitiesController@filter']);
    Route::resource('visibilities', 'Api\VisibilitiesController')->only(['index', 'show']);

    Route::match(['get', 'post'], 'occurrence-types/filter', ['as' => 'occurrence-types.filter', 'uses' => 'Api\OccurrenceTypesController@filter']);
    Route::resource('occurrence-types', 'Api\OccurrenceTypesController')->only(['index', 'show']);

    Route::match(['get', 'post'], 'occurrence-weeks/filter', ['as' => 'occurrence-weeks.filter', 'uses' => 'Api\OccurrenceWeeksController@filter']);
    Route::resource('occurrence-weeks', 'Api\OccurrenceWeeksController')->only(['index', 'show']);

    Route::match(['get', 'post'], 'occurrence-days/filter', ['as' => 'occurrence-days.filter', 'uses' => 'Api\OccurrenceDaysController@filter']);
    Route::resource('occurrence-days', 'Api\OccurrenceDaysController')->only(['index', 'show']);

    // photo management endpoints
    Route::post('photos/{photo}/set-primary', 'Api\\PhotosController@setPrimary');
    Route::post('photos/{photo}/unset-primary', 'Api\\PhotosController@unsetPrimary');
    Route::delete('photos/{photo}', 'Api\\PhotosController@destroy');
});

// routes protected by the shield middleware
Route::middleware('shield')->name('shield.')->group(function () {
});

// calendar routes - these are used by the web app for dynamic loading
Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api');
Route::get('tag-calendar-events', 'EventsController@tagCalendarEventsApi')->name('tagCalendarEvents.api');

// user registration endpoint
Route::post('register', 'Api\\RegisterController@register');

// email verification endpoint
// disabled signature for testing
Route::get('email/verify/{id}/{hash}', 'Api\\EmailVerificationController@verify')
    ->middleware('throttle:6,1')
    ->name('api.verification.verify');

// password reset endpoints
Route::post('user/send-password-reset-email', 'Api\\PasswordResetController@sendPasswordResetEmail');
Route::post('user/reset-password', 'Api\\PasswordResetController@resetPassword');
