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

// Public typeahead search. Visibility scoping uses the optionally-authenticated user.
Route::get('search', ['as' => 'api.search', 'uses' => '\App\Http\Controllers\Api\SearchController@index']);

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

    Route::match(['get', 'post'], 'activities/filter', ['as' => 'activities.filter', 'uses' => '\App\Http\Controllers\Api\ActivityController@filter']);
    Route::get('activities/reset', ['as' => 'activities.reset', 'uses' => '\App\Http\Controllers\Api\ActivityController@reset']);
    Route::get('activities/rpp-reset', ['as' => 'activities.rppReset', 'uses' => '\App\Http\Controllers\Api\ActivityController@rppReset']);
    Route::resource('activities', \App\Http\Controllers\Api\ActivityController::class);

    Route::match(['get', 'post'], 'blogs/filter', ['as' => 'blogs.filter', 'uses' => '\App\Http\Controllers\Api\BlogsController@filter']);
    Route::get('blogs/reset', ['as' => 'blogs.reset', 'uses' => '\App\Http\Controllers\Api\BlogsController@reset']);
    Route::get('blogs/rpp-reset', ['as' => 'blogs.rppReset', 'uses' => '\App\Http\Controllers\Api\BlogsController@rppReset']);
    Route::put('blogs/{blog}', [\App\Http\Controllers\Api\BlogsController::class, 'update'])->name('blogs.update');
    Route::patch('blogs/{blog}', [\App\Http\Controllers\Api\BlogsController::class, 'patch'])->name('blogs.patch');
    Route::apiResource('blogs', \App\Http\Controllers\Api\BlogsController::class)->except(['update']);

    Route::get('events/attending', ['as' => 'events.attending', 'uses' => '\App\Http\Controllers\Api\EventsController@indexAttending']);
    Route::get('events/recommended', ['as' => 'events.recommended', 'uses' => '\App\Http\Controllers\Api\EventsController@indexRecommended'])->middleware('auth:sanctum');
    Route::get('events/popular', ['as' => 'events.popular', 'uses' => '\App\Http\Controllers\Api\EventsController@popular']);
    Route::get('events/by-date/{year}/{month?}/{day?}', [\App\Http\Controllers\Api\EventsController::class, 'indexByDate'])
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])')
    ->where('day', '(0?[1-9]|[12][0-9]|3[01])');
    
    Route::get('events/reset', ['as' => 'events.reset', 'uses' => '\App\Http\Controllers\Api\EventsController@reset']);
    Route::get('events/rpp-reset', ['as' => 'events.rppReset', 'uses' => '\App\Http\Controllers\Api\EventsController@rppReset']);
    
    Route::get('events/{event}/photos', ['as' => 'events.photos', 'uses' => '\App\Http\Controllers\Api\EventsController@photos']);
    Route::get('events/{event}/all-photos', ['as' => 'events.allPhotos', 'uses' => '\App\Http\Controllers\Api\EventsController@allPhotos']);
    Route::post('events/{id}/photos', [\App\Http\Controllers\Api\EventsController::class, 'addPhoto']);
    Route::post('events/{id}/instagram-post', [\App\Http\Controllers\Api\EventInstagramController::class, 'postCarouselToInstagramApi']);
    Route::get('events/{event}/embeds', ['as' => 'events.embeds', 'uses' => '\App\Http\Controllers\Api\EventsController@embeds']);
    Route::get('events/{event}/minimal-embeds', ['as' => 'events.minimalEmbeds', 'uses' => '\App\Http\Controllers\Api\EventsController@minimalEmbeds']);

    Route::post('events/{event}/attend', [\App\Http\Controllers\Api\EventsController::class, 'attendJson']);
    Route::delete('events/{event}/attend', [\App\Http\Controllers\Api\EventsController::class, 'unattendJson']);

    Route::put('events/{event}', [\App\Http\Controllers\Api\EventsController::class, 'update'])->name('events.update');
    Route::patch('events/{event}', [\App\Http\Controllers\Api\EventsController::class, 'patch'])->name('events.patch');
    Route::resource('events', \App\Http\Controllers\Api\EventsController::class)->except(['update']);

    Route::get('entities/{entity}/photos', ['as' => 'entities.photos', 'uses' => '\App\Http\Controllers\Api\EntitiesController@photos']);
    Route::get('entities/{entity}/links', ['as' => 'entities.links', 'uses' => '\App\Http\Controllers\Api\EntitiesController@links']);
    Route::get('entities/{entity}/locations', ['as' => 'entities.locations', 'uses' => '\App\Http\Controllers\Api\EntitiesController@locations']);
    Route::get('entities/{entity}/contacts', ['as' => 'entities.contacts', 'uses' => '\App\Http\Controllers\Api\EntitiesController@contacts']);
    Route::get('entities/{entity}/embeds', ['as' => 'entities.embeds', 'uses' => '\App\Http\Controllers\Api\EntitiesController@embeds']);
    Route::get('entities/{entity}/minimal-embeds', ['as' => 'entities.minimalEmbeds', 'uses' => '\App\Http\Controllers\Api\EntitiesController@minimalEmbeds']);
    Route::post('entities/{id}/photos', [\App\Http\Controllers\Api\EntitiesController::class, 'addPhoto']);
    Route::post('entities/{id}/links', [\App\Http\Controllers\Api\EntitiesController::class, 'addLink']);
    Route::post('entities/{id}/locations', [\App\Http\Controllers\Api\EntitiesController::class, 'addLocation']);
    Route::post('entities/{id}/contacts', [\App\Http\Controllers\Api\EntitiesController::class, 'addContact']);
    Route::put('entities/{id}/links/{linkId}', [\App\Http\Controllers\Api\EntitiesController::class, 'updateLink']);
    Route::patch('entities/{id}/links/{linkId}', [\App\Http\Controllers\Api\EntitiesController::class, 'patchLink']);
    Route::put('entities/{id}/locations/{locationId}', [\App\Http\Controllers\Api\EntitiesController::class, 'updateLocation']);
    Route::patch('entities/{id}/locations/{locationId}', [\App\Http\Controllers\Api\EntitiesController::class, 'patchLocation']);
    Route::put('entities/{id}/contacts/{contactId}', [\App\Http\Controllers\Api\EntitiesController::class, 'updateContact']);
    Route::patch('entities/{id}/contacts/{contactId}', [\App\Http\Controllers\Api\EntitiesController::class, 'patchContact']);
    Route::delete('entities/{id}/links/{linkId}', [\App\Http\Controllers\Api\EntitiesController::class, 'deleteLink']);
    Route::delete('entities/{id}/locations/{locationId}', [\App\Http\Controllers\Api\EntitiesController::class, 'deleteLocation']);
    Route::delete('entities/{id}/contacts/{contactId}', [\App\Http\Controllers\Api\EntitiesController::class, 'deleteContact']);
    Route::post('entities/{entity}/follow', [\App\Http\Controllers\Api\EntitiesController::class, 'followJson'])->middleware('auth:sanctum');
    Route::post('entities/{entity}/unfollow', [\App\Http\Controllers\Api\EntitiesController::class, 'unfollowJson'])->middleware('auth:sanctum');
    Route::get('entities/popular', ['as' => 'entities.popular', 'uses' => '\App\Http\Controllers\Api\EntitiesController@popular']);
    Route::get('entities/following', ['as' => 'entities.following', 'uses' => '\App\Http\Controllers\Api\EntitiesController@indexFollowingJson'])->middleware('auth:sanctum');
    // PUT replaces the resource; PATCH applies a partial update. Both are
    // wired explicitly so they map to distinct controller methods rather than
    // the single update() endpoint Route::resource would otherwise generate.
    Route::put('entities/{entity}', [\App\Http\Controllers\Api\EntitiesController::class, 'update'])->name('entities.update');
    Route::patch('entities/{entity}', [\App\Http\Controllers\Api\EntitiesController::class, 'patch'])->name('entities.patch');
    Route::resource('entities', \App\Http\Controllers\Api\EntitiesController::class)->except(['update']);

    Route::match(['get', 'post'], 'entity-types/filter', ['as' => 'entityType.filter', 'uses' => '\App\Http\Controllers\Api\EntityTypesController@filter']);
    Route::get('entity-types/reset', ['as' => 'entity-types.reset', 'uses' => '\App\Http\Controllers\Api\EntityTypesController@reset']);
    Route::get('entity-types/rpp-reset', ['as' => 'entity-types.rppReset', 'uses' => '\App\Http\Controllers\Api\EntityTypesController@rppReset']);
    Route::put('entity-types/{entity_type}', [\App\Http\Controllers\Api\EntityTypesController::class, 'update'])->name('entity-types.update');
    Route::patch('entity-types/{entity_type}', [\App\Http\Controllers\Api\EntityTypesController::class, 'patch'])->name('entity-types.patch');
    Route::resource('entity-types', \App\Http\Controllers\Api\EntityTypesController::class)->except(['update']);

    Route::match(['get', 'post'], 'entity-statuses/filter', ['as' => 'entityStatus.filter', 'uses' => '\App\Http\Controllers\Api\EntityStatusesController@filter']);
    Route::get('entity-statuses/reset', ['as' => 'entity-statuses.reset', 'uses' => '\App\Http\Controllers\Api\EntityStatusesController@reset']);
    Route::get('entity-statuses/rpp-reset', ['as' => 'entity-statuses.rppReset', 'uses' => '\App\Http\Controllers\Api\EntityStatusesController@rppReset']);
    Route::put('entity-statuses/{entity_status}', [\App\Http\Controllers\Api\EntityStatusesController::class, 'update'])->name('entity-statuses.update');
    Route::patch('entity-statuses/{entity_status}', [\App\Http\Controllers\Api\EntityStatusesController::class, 'patch'])->name('entity-statuses.patch');
    Route::resource('entity-statuses', \App\Http\Controllers\Api\EntityStatusesController::class)->except(['update']);

    Route::match(['get', 'post'], 'event-types/filter', ['as' => 'eventType.filter', 'uses' => '\App\Http\Controllers\Api\EventTypesController@filter']);
    Route::get('event-types/reset', ['as' => 'event-types.reset', 'uses' => '\App\Http\Controllers\Api\EventTypesController@reset']);
    Route::get('event-types/rpp-reset', ['as' => 'event-types.rppReset', 'uses' => '\App\Http\Controllers\Api\EventTypesController@rppReset']);
    Route::put('event-types/{event_type}', [\App\Http\Controllers\Api\EventTypesController::class, 'update'])->name('event-types.update');
    Route::patch('event-types/{event_type}', [\App\Http\Controllers\Api\EventTypesController::class, 'patch'])->name('event-types.patch');
    Route::resource('event-types', \App\Http\Controllers\Api\EventTypesController::class)->except(['update']);

    Route::match(['get', 'post'], 'event-statuses/filter', ['as' => 'eventStatus.filter', 'uses' => '\App\Http\Controllers\Api\EventStatusesController@filter']);
    Route::get('event-statuses/reset', ['as' => 'event-statuses.reset', 'uses' => '\App\Http\Controllers\Api\EventStatusesController@reset']);
    Route::get('event-statuses/rpp-reset', ['as' => 'event-statuses.rppReset', 'uses' => '\App\Http\Controllers\Api\EventStatusesController@rppReset']);
    Route::put('event-statuses/{event_status}', [\App\Http\Controllers\Api\EventStatusesController::class, 'update'])->name('event-statuses.update');
    Route::patch('event-statuses/{event_status}', [\App\Http\Controllers\Api\EventStatusesController::class, 'patch'])->name('event-statuses.patch');
    Route::resource('event-statuses', \App\Http\Controllers\Api\EventStatusesController::class)->except(['update']);

    Route::match(['get', 'post'], 'forums/filter', ['as' => 'forums.filter', 'uses' => '\App\Http\Controllers\Api\ForumsController@filter']);
    Route::get('forums/reset', ['as' => 'forums.reset', 'uses' => '\App\Http\Controllers\Api\ForumsController@reset']);
    Route::get('forums/rpp-reset', ['as' => 'forums.rppReset', 'uses' => '\App\Http\Controllers\Api\ForumsController@rppReset']);
    Route::put('forums/{forum}', [\App\Http\Controllers\Api\ForumsController::class, 'update'])->name('forums.update');
    Route::patch('forums/{forum}', [\App\Http\Controllers\Api\ForumsController::class, 'patch'])->name('forums.patch');
    Route::apiResource('forums', \App\Http\Controllers\Api\ForumsController::class)->except(['update']);

    Route::match(['get', 'post'], 'links/filter', ['as' => 'links.filter', 'uses' => '\App\Http\Controllers\Api\LinksController@filter']);
    Route::get('links/reset', ['as' => 'links.reset', 'uses' => '\App\Http\Controllers\Api\LinksController@reset']);
    Route::get('links/rpp-reset', ['as' => 'links.rppReset', 'uses' => '\App\Http\Controllers\Api\LinksController@rppReset']);
    Route::apiResource('links', \App\Http\Controllers\Api\LinksController::class);
    Route::put('menus/{menu}', [\App\Http\Controllers\Api\MenusController::class, 'update'])->name('menus.update');
    Route::patch('menus/{menu}', [\App\Http\Controllers\Api\MenusController::class, 'patch'])->name('menus.patch');
    Route::resource('menus', \App\Http\Controllers\Api\MenusController::class)->except(['update']);

    Route::match(['get', 'post'], 'locations/filter', ['as' => 'locations.filter', 'uses' => '\App\Http\Controllers\Api\LocationsController@filter']);
    Route::get('locations/reset', ['as' => 'locations.reset', 'uses' => '\App\Http\Controllers\Api\LocationsController@reset']);
    Route::get('locations/rpp-reset', ['as' => 'locations.rppReset', 'uses' => '\App\Http\Controllers\Api\LocationsController@rppReset']);
    Route::put('locations/{location}', [\App\Http\Controllers\Api\LocationsController::class, 'update'])->name('locations.update');
    Route::patch('locations/{location}', [\App\Http\Controllers\Api\LocationsController::class, 'patch'])->name('locations.patch');
    Route::resource('locations', \App\Http\Controllers\Api\LocationsController::class)->except(['update']);

    Route::get('series/reset', ['as' => 'series.reset', 'uses' => '\App\Http\Controllers\Api\SeriesController@reset']);
    Route::get('series/rpp-reset', ['as' => 'series.rppReset', 'uses' => '\App\Http\Controllers\Api\SeriesController@rppReset']);
    Route::get('series/{series}/photos', ['as' => 'series.photos', 'uses' => '\App\Http\Controllers\Api\SeriesController@photos']);
    Route::get('series/{series}/all-photos', ['as' => 'series.allPhotos', 'uses' => '\App\Http\Controllers\Api\SeriesController@allPhotos']);
    Route::post('series/{id}/photos', [\App\Http\Controllers\Api\SeriesController::class, 'addPhoto']);
    Route::post('series/{series}/follow', [\App\Http\Controllers\Api\SeriesController::class, 'followJson'])->middleware('auth:sanctum');
    Route::post('series/{series}/unfollow', [\App\Http\Controllers\Api\SeriesController::class, 'unfollowJson'])->middleware('auth:sanctum');
    Route::get('series/popular', ['as' => 'series.popular', 'uses' => '\App\Http\Controllers\Api\SeriesController@popular']);
    Route::put('series/{series}', [\App\Http\Controllers\Api\SeriesController::class, 'update'])->name('series.update');
    Route::patch('series/{series}', [\App\Http\Controllers\Api\SeriesController::class, 'patch'])->name('series.patch');
    Route::apiResource('series', \App\Http\Controllers\Api\SeriesController::class)->except(['update']);

    Route::match(['get', 'post'], 'tags/filter', ['as' => 'tags.filter', 'uses' => '\App\Http\Controllers\Api\TagsController@filter']);
    Route::get('tags/reset', ['as' => 'tags.reset', 'uses' => '\App\Http\Controllers\Api\TagsController@reset']);
    Route::get('tags/rpp-reset', ['as' => 'tags.rppReset', 'uses' => '\App\Http\Controllers\Api\TagsController@rppReset']);
    Route::post('tags/{tag}/follow', [\App\Http\Controllers\Api\TagsController::class, 'followJson'])->middleware('auth:sanctum');
    Route::post('tags/{tag}/unfollow', [\App\Http\Controllers\Api\TagsController::class, 'unfollowJson'])->middleware('auth:sanctum');
    Route::delete('tags/{tag}', [\App\Http\Controllers\Api\TagsController::class, 'destroy']);
    Route::get('tags/{tag}/related-tags', ['as' => 'tags.relatedTags', 'uses' => '\App\Http\Controllers\Api\TagsController@relatedTags']);
    Route::get('tags/popular', ['as' => 'tags.popular', 'uses' => '\App\Http\Controllers\Api\TagsController@popular']);
    Route::resource('tags', \App\Http\Controllers\Api\TagsController::class)->except(['destroy']);
    Route::match(['get', 'post'], 'tag-types/filter', ['as' => 'tag-types.filter', 'uses' => '\App\Http\Controllers\Api\TagTypesController@filter']);
    Route::resource('tag-types', \App\Http\Controllers\Api\TagTypesController::class)->only(['index', 'show']);


    Route::match(['get', 'post'], 'roles/filter', ['as' => 'roles.filter', 'uses' => '\App\Http\Controllers\Api\RolesController@filter']);
    Route::get('roles/reset', ['as' => 'roles.reset', 'uses' => '\App\Http\Controllers\Api\RolesController@reset']);
    Route::get('roles/rpp-reset', ['as' => 'roles.rppReset', 'uses' => '\App\Http\Controllers\Api\RolesController@rppReset']);
    Route::put('roles/{role}', [\App\Http\Controllers\Api\RolesController::class, 'update'])->name('roles.update');
    Route::patch('roles/{role}', [\App\Http\Controllers\Api\RolesController::class, 'patch'])->name('roles.patch');
    Route::resource('roles', \App\Http\Controllers\Api\RolesController::class)->except(['update']);


    Route::match(['get', 'post'], 'posts/filter', ['as' => 'posts.filter', 'uses' => '\App\Http\Controllers\Api\PostsController@filter']);
    Route::get('posts/reset', ['as' => 'posts.reset', 'uses' => '\App\Http\Controllers\Api\PostsController@reset']);
    Route::get('posts/rpp-reset', ['as' => 'posts.rppReset', 'uses' => '\App\Http\Controllers\Api\PostsController@rppReset']);
    Route::put('posts/{post}', [\App\Http\Controllers\Api\PostsController::class, 'update'])->name('posts.update');
    Route::patch('posts/{post}', [\App\Http\Controllers\Api\PostsController::class, 'patch'])->name('posts.patch');
    Route::apiResource('posts', \App\Http\Controllers\Api\PostsController::class)->except(['update']);

    Route::match(['get', 'post'], 'threads/filter', ['as' => 'threads.filter', 'uses' => '\App\Http\Controllers\Api\ThreadsController@filter']);
    Route::get('threads/reset', ['as' => 'threads.reset', 'uses' => '\App\Http\Controllers\Api\ThreadsController@reset']);
    Route::get('threads/rpp-reset', ['as' => 'threads.rppReset', 'uses' => '\App\Http\Controllers\Api\ThreadsController@rppReset']);
    Route::get('threads/{threadId}/posts', ['as' => 'threads.posts', 'uses' => '\App\Http\Controllers\Api\ThreadsController@posts']);
    Route::put('threads/{thread}', [\App\Http\Controllers\Api\ThreadsController::class, 'update'])->name('threads.update');
    Route::patch('threads/{thread}', [\App\Http\Controllers\Api\ThreadsController::class, 'patch'])->name('threads.patch');
    Route::apiResource('threads', \App\Http\Controllers\Api\ThreadsController::class)->except(['update']);

    Route::match(['get', 'post'], 'users/filter', ['as' => 'users.filter', 'uses' => '\App\Http\Controllers\Api\UsersController@filter']);
    Route::get('users/reset', ['as' => 'users.reset', 'uses' => '\App\Http\Controllers\Api\UsersController@reset']);
    Route::get('users/rpp-reset', ['as' => 'users.rppReset', 'uses' => '\App\Http\Controllers\Api\UsersController@rppReset']);
    Route::get('users/{user}/events-attending', ['as' => 'users.events-attending', 'uses' => '\App\Http\Controllers\Api\UsersController@eventsAttending']);
    Route::apiResource('users', \App\Http\Controllers\Api\UsersController::class);

    Route::match(['get', 'post'], 'visibilities/filter', ['as' => 'visibilities.filter', 'uses' => '\App\Http\Controllers\Api\VisibilitiesController@filter']);
    Route::resource('visibilities', \App\Http\Controllers\Api\VisibilitiesController::class)->only(['index', 'show']);

    Route::match(['get', 'post'], 'occurrence-types/filter', ['as' => 'occurrence-types.filter', 'uses' => '\App\Http\Controllers\Api\OccurrenceTypesController@filter']);
    Route::resource('occurrence-types', \App\Http\Controllers\Api\OccurrenceTypesController::class)->only(['index', 'show']);

    Route::match(['get', 'post'], 'occurrence-weeks/filter', ['as' => 'occurrence-weeks.filter', 'uses' => '\App\Http\Controllers\Api\OccurrenceWeeksController@filter']);
    Route::resource('occurrence-weeks', \App\Http\Controllers\Api\OccurrenceWeeksController::class)->only(['index', 'show']);

    Route::match(['get', 'post'], 'occurrence-days/filter', ['as' => 'occurrence-days.filter', 'uses' => '\App\Http\Controllers\Api\OccurrenceDaysController@filter']);
    Route::resource('occurrence-days', \App\Http\Controllers\Api\OccurrenceDaysController::class)->only(['index', 'show']);

    // photo management endpoints
    Route::post('photos/{photo}/set-primary', [\App\Http\Controllers\Api\PhotosController::class, 'setPrimary']);
    Route::post('photos/{photo}/unset-primary', [\App\Http\Controllers\Api\PhotosController::class, 'unsetPrimary']);
    Route::delete('photos/{photo}', [\App\Http\Controllers\Api\PhotosController::class, 'destroy']);
});

// calendar routes - these are used by the web app for dynamic loading
Route::get('calendar-events', [\App\Http\Controllers\EventsController::class, 'calendarEventsApi'])->name('calendarEvents.api');
Route::get('tag-calendar-events', [\App\Http\Controllers\EventsController::class, 'tagCalendarEventsApi'])->name('tagCalendarEvents.api');

// user registration endpoint
Route::post('register', [\App\Http\Controllers\Api\RegisterController::class, 'register']);

// Email verification endpoint — signature enforced to prevent
// account takeover via crafted URLs.
Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\Api\EmailVerificationController::class, 'verify'])
    ->middleware(['signed:relative', 'throttle:6,1'])
    ->name('api.verification.verify');

// password reset endpoints
Route::post('user/send-password-reset-email', [\App\Http\Controllers\Api\PasswordResetController::class, 'sendPasswordResetEmail']);
Route::post('user/reset-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetPassword']);
