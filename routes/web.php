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

// what is this  for?
use App\Events\EventUpdated;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Link;
use App\Models\Location;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// NOTE: we register `verify` routes explicitly below so that the
// `verification.verify` route gets the `signed` middleware. The version
// of Auth::routes(['verify' => true]) shipped by laravel/ui does NOT
// apply it, leaving accounts open to verification via crafted URLs.
Auth::routes();

// Email verification routes — `verification.verify` MUST be signed.
Route::get('email/verify', [\App\Http\Controllers\Auth\VerificationController::class, 'show'])
    ->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\VerificationController::class, 'verify'])
    ->middleware(['signed:relative', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('email/resend', [\App\Http\Controllers\Auth\VerificationController::class, 'resend'])
    ->name('verification.resend');

Route::get('tokens/test', function () {
    return ['data' => 'has event check'];
})->middleware('auth:sanctum');

Route::view('/api/docs', 'docs.swagger');


Route::get('/', [\App\Http\Controllers\PagesController::class, 'home'])->name('home');
Route::get('/home', [\App\Http\Controllers\PagesController::class, 'home'])->name('pages.home');

Route::get('about', [\App\Http\Controllers\PagesController::class, 'about']);
Route::get('privacy', [\App\Http\Controllers\PagesController::class, 'privacy']);
Route::get('tos', [\App\Http\Controllers\PagesController::class, 'tos']);
Route::get('radar', [\App\Http\Controllers\PagesController::class, 'radar'])->middleware('auth')->name('radar');
Route::get('popular', [\App\Http\Controllers\PagesController::class, 'popular'])->name('pages.popular');

// Post-signup "Getting To Know You" onboarding (issue #901)
Route::middleware('auth')->group(function () {
    Route::get('onboarding/data', [\App\Http\Controllers\OnboardingController::class, 'data'])->name('onboarding.data');
    Route::post('onboarding/follow', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('onboarding/dismiss', [\App\Http\Controllers\OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
});

Route::get('help', [\App\Http\Controllers\PagesController::class, 'help']);
Route::get('all-modules', [\App\Http\Controllers\PagesController::class, 'allModules'])->name('pages.allModules');

Route::get('calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
Route::get('calendar/by-date/{year}/{month?}/{day?}', [\App\Http\Controllers\CalendarController::class, 'indexByDate'])->name('calendar.byDate')
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])$')
    ->where('day', '[0-3][0-9]');
Route::get('calendar/tag/{tag}', [\App\Http\Controllers\CalendarController::class, 'calendarTags'])->name('calendar.tag');
Route::get('tag-calendar', [\App\Http\Controllers\CalendarController::class, 'calendarTagOnly'])->name('tag-calendar');
Route::get('calendar/related-to/{slug}', [\App\Http\Controllers\CalendarController::class, 'calendarRelatedTo']);
Route::get('calendar/free', [\App\Http\Controllers\CalendarController::class, 'calendarFree'])->name('calendar.free');
Route::get('calendar/attending', [\App\Http\Controllers\CalendarController::class, 'calendarAttending'])->name('calendar.attending');
Route::get('calendar/type/{tag}', [\App\Http\Controllers\CalendarController::class, 'calendarEventTypes'])->name('calendar.type');
Route::get('calendar/min-age/{age}', [\App\Http\Controllers\CalendarController::class, 'calendarMinAge'])->name('calendar.minAge');

Route::get('search', [\App\Http\Controllers\PagesController::class, 'search'])->name('pages.search');

Route::match(['get', 'post'], 'auto-relate-entity/{id}', [
    'as' => 'pages.relate',
    'uses' => '\App\Http\Controllers\PagesController@autoRelateEntity',
]);

Route::get('users/{id}/notify', [
    'as' => 'users.notify',
    'uses' => '\App\Http\Controllers\UsersController@notifyUser',
]);
Route::match(['get', 'post'], 'activity/filter', ['as' => 'activities.filter', 'uses' => '\App\Http\Controllers\ActivityController@index']);
Route::get('activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activities.index');
Route::get('activity/graph', [\App\Http\Controllers\ActivityController::class, 'graph'])->name('activities.graph')->middleware(['auth', 'can:admin']);
Route::get('activity/graph/export', [\App\Http\Controllers\ActivityController::class, 'exportGraph'])->name('activities.graph.export')->middleware(['auth', 'can:admin']);
Route::get('activity/reset', ['as' => 'activities.reset', 'uses' => '\App\Http\Controllers\ActivityController@reset']);
Route::get('activity/rpp-reset', ['as' => 'activities.rppReset', 'uses' => '\App\Http\Controllers\ActivityController@rppReset']);

Route::get('tools', [\App\Http\Controllers\PagesController::class, 'tools'])->name('pages.tools');
Route::post('invite', [\App\Http\Controllers\PagesController::class, 'invite'])->name('pages.invite');

Route::get('events/importPhotos', [
    'as' => 'events.importPhotos',
    'uses' => '\App\Http\Controllers\EventsController@importPhotos',
]);


Route::bind('users', function ($id) {
    return App\Models\User::whereId($id)->firstOrFail();
});

Route::get('impersonate/{user}', function (User $user) {
    Auth::login($user);

    return redirect('/');
})->middleware('can:admin')->name('user.impersonate');

Route::post('users/{id}/photos', [\App\Http\Controllers\UsersController::class, 'addPhoto']);
Route::delete('users/{id}/photos/{photo_id}', [\App\Http\Controllers\UsersController::class, 'deletePhoto']);

Route::get('users/{id}/activate', [
    'as' => 'users.activate',
    'uses' => '\App\Http\Controllers\UsersController@activate',
]);

Route::get('users/{id}/reminder', [
    'as' => 'users.reminder',
    'uses' => '\App\Http\Controllers\UsersController@reminder',
]);

Route::get('users/{id}/weekly', [
    'as' => 'users.weekly',
    'uses' => '\App\Http\Controllers\UsersController@weekly',
]);

Route::get('users/{id}/suspend', [
    'as' => 'users.suspend',
    'uses' => '\App\Http\Controllers\UsersController@suspend',
]);

Route::get('users/{id}/ical', [
    'as' => 'users.ical',
    'uses' => '\App\Http\Controllers\UsersController@ical',
]);

Route::get('users/{id}/delete', [
    'as' => 'users.delete',
    'uses' => '\App\Http\Controllers\UsersController@delete',
]);

Route::get('users/{id}/reset-password', [
    'as' => 'users.showResetPassword',
    'uses' => '\App\Http\Controllers\UsersController@showResetPassword',
])->middleware('can:grant_access');

Route::post('users/{id}/reset-password', [
    'as' => 'users.resetPassword',
    'uses' => '\App\Http\Controllers\UsersController@resetPassword',
])->middleware('can:grant_access');

Route::post('users/{id}/export-data', [
    'as' => 'users.exportData',
    'uses' => '\App\Http\Controllers\UsersController@exportData',
])->middleware('auth');

Route::post('users/{id}/restart-onboarding', [
    'as' => 'users.restartOnboarding',
    'uses' => '\App\Http\Controllers\UsersController@restartOnboarding',
])->middleware('auth');

Route::get('exports/download/{filename}', [
    'as' => 'exports.download',
    'uses' => '\App\Http\Controllers\UsersController@downloadExport',
])->middleware('signed');

Route::post('purge', [\App\Http\Controllers\UsersController::class, 'purge'])->name('users.purge');

Route::match(['get', 'post'], 'users/{id}/attending', [\App\Http\Controllers\EventsController::class, 'indexUserAttending'])->name('users.attending');
Route::match(['get', 'post'], 'users/{id}/attending-ical', [\App\Http\Controllers\EventsController::class, 'indexUserAttendingIcal'])->name('users.attendingIcal');
Route::match(['get', 'post'], 'users/{id}/interested-ical', [\App\Http\Controllers\EventsController::class, 'indexUserInterestedIcal'])->name('users.interestedIcal');
Route::match(['get', 'post'], 'users/{id}/reset-user-attending', ['as' => 'users.resetUserAttending', 'uses' => '\App\Http\Controllers\EventsController@resetUserAttending']);
Route::get('users/{id}/rpp-reset-user-attending', ['as' => 'users.rppResetUserAttending', 'uses' => '\App\Http\Controllers\EventsController@rppResetUserAttending']);

Route::match(['get', 'post'], 'users/filter', ['as' => 'users.filter', 'uses' => '\App\Http\Controllers\UsersController@filter']);
Route::get('users/reset', ['as' => 'users.reset', 'uses' => '\App\Http\Controllers\UsersController@reset']);
Route::get('users/rpp-reset', ['as' => 'users.rppReset', 'uses' => '\App\Http\Controllers\UsersController@rppReset']);

Route::resource('users', \App\Http\Controllers\UsersController::class)->except(['show'])->middleware('auth');
Route::get('users/{user}', [\App\Http\Controllers\UsersController::class, 'show'])->name('users.show');

Route::get('profile/{user}', [\App\Http\Controllers\UsersController::class, 'show'])->name('users.profile-show');
Route::get('profile', [\App\Http\Controllers\UsersController::class, 'profile'])->name('users.profile');
// PHOTOS
Route::get('photos/reset', ['as' => 'photos.reset', 'uses' => '\App\Http\Controllers\PhotosController@reset']);
Route::get('photos/rpp-reset', ['as' => 'photos.rppReset', 'uses' => '\App\Http\Controllers\PhotosController@rppReset']);
Route::delete('photos/{id}', [\App\Http\Controllers\PhotosController::class, 'destroy']);
Route::post('photos/{id}/set-primary', [\App\Http\Controllers\PhotosController::class, 'setPrimary']);
Route::post('photos/{id}/unset-primary', [\App\Http\Controllers\PhotosController::class, 'unsetPrimary']);
Route::post('photos/{id}/set-event', [\App\Http\Controllers\PhotosController::class, 'setEvent']);
Route::post('photos/{id}/unset-event', [\App\Http\Controllers\PhotosController::class, 'unsetEvent']);
Route::get('photos/tag/{tag}', [\App\Http\Controllers\PhotosController::class, 'indexTags'])->name('photos.tag');

Route::match(['get', 'post'], 'photos/filter', ['as' => 'photos.filter', 'uses' => '\App\Http\Controllers\PhotosController@filter']);

Route::bind('photos', function ($id) {
    return Photo::whereId($id)->firstOrFail();
});

Route::resource('photos', \App\Http\Controllers\PhotosController::class);

// EVENTS
Route::get('events/create-series', [
    'as' => 'events.createSeries',
    'uses' => '\App\Http\Controllers\EventsController@createSeries',
]);
Route::get('events/create-thread', [
    'as' => 'events.createThread',
    'uses' => '\App\Http\Controllers\EventsController@createThread',
]);

Route::get('events/{id}/duplicate', [
    'as' => 'events.duplicate',
    'uses' => '\App\Http\Controllers\EventsController@duplicate',
]);

Route::get('events/dispatch', function () {
    EventUpdated::dispatch();

    return 'test';
});
Route::get('update', function () {
    EventUpdated::dispatch(new Event());
});

Route::get('events/today', [\App\Http\Controllers\EventsController::class, 'indexToday']);
Route::match(['get', 'post'], 'events/grid', [\App\Http\Controllers\EventsController::class, 'indexGrid'])->name('events.grid');
Route::get('events/grid/tag/{slug}', [\App\Http\Controllers\EventsController::class, 'indexGridTags'])->name('events.grid.tag');
Route::get('events/grid/by-date/{year}/{month?}/{day?}', [\App\Http\Controllers\EventsController::class, 'indexGridByDate'])->name('events.grid.byDate')
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])')
    ->where('day', '(0?[1-9]|[12][0-9]|3[01])');
Route::get('events/grid/related-to/{slug}', [\App\Http\Controllers\EventsController::class, 'indexGridRelatedTo'])->name('events.grid.relatedto');
Route::get('events/grid/type/{slug}', [\App\Http\Controllers\EventsController::class, 'indexGridTypes'])->name('events.grid.type');
Route::get('events/grid/series/{slug}', [\App\Http\Controllers\EventsController::class, 'indexGridSeries'])->name('events.grid.series');
Route::get('events/grid/apply-filter', ['as' => 'events.grid.applyFilterFromUrl', 'uses' => '\App\Http\Controllers\EventsController@applyGridFilterFromUrl']);
Route::match(['get', 'post'], 'events/photos', [\App\Http\Controllers\EventsController::class, 'indexPhoto'])->name('events.photo');
Route::get('events/future', [\App\Http\Controllers\EventsController::class, 'indexFuture'])->name('events.future');
Route::get('events/upcoming/{date?}', [\App\Http\Controllers\EventsController::class, 'indexUpcoming'])->name('events.upcoming');
Route::get('events/add/{date?}', [\App\Http\Controllers\EventsController::class, 'indexAdd'])->name('events.add')
    ->where('date', '[1-2][0-9][0-9][0-9][0-3][0-9][0-9][0-9]');    
Route::get('events/past', [\App\Http\Controllers\EventsController::class, 'indexPast']);
Route::get('events/week', [\App\Http\Controllers\EventsController::class, 'indexWeek'])->name('events.week');
Route::get('events/starting/{date}', [\App\Http\Controllers\EventsController::class, 'indexStarting'])
    ->where('date', '[0-9]{8}|[0-9]{4}-[0-9]{2}-[0-9]{2}');
Route::get('events/{id}/load-embeds', [\App\Http\Controllers\EventsController::class, 'loadEmbeds']);
Route::get('events/{id}/load-minimal-embeds', [\App\Http\Controllers\EventsController::class, 'loadMinimalEmbeds']);
Route::get('events/{slug}/minimal-embeds', [\App\Http\Controllers\EventsController::class, 'loadMinimalEmbedsBySlug']);
Route::get('events/{slug}/embeds', [\App\Http\Controllers\EventsController::class, 'loadEmbedsBySlug']);
Route::get('events/by-date/{year}/{month?}/{day?}', [\App\Http\Controllers\EventsController::class, 'indexByDate'])
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])$')
    ->where('day', '(0?[1-9]|[12][0-9]|3[01])');
Route::get('events/daily', [\App\Http\Controllers\EventsController::class, 'daily']);
Route::get('events/day/{day}', [\App\Http\Controllers\EventsController::class, 'day'])->name('events.day')
    ->where('day', '[12][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])');
Route::match(['get', 'post'], 'events/attending', [\App\Http\Controllers\EventsController::class, 'indexAttending'])->name('events.attending');
Route::match(['get', 'post'], 'events/filter', ['as' => 'events.filter', 'uses' => '\App\Http\Controllers\EventsController@filter']);
Route::get('events/apply-filter', ['as' => 'events.applyFilterFromUrl', 'uses' => '\App\Http\Controllers\EventsController@applyFilterFromUrl']);
Route::get('events/reset', ['as' => 'events.reset', 'uses' => '\App\Http\Controllers\EventsController@reset']);
Route::get('events/rpp-reset', ['as' => 'events.rppReset', 'uses' => '\App\Http\Controllers\EventsController@rppReset']);

// FB access token
Route::get('fb-access', [\App\Http\Controllers\EventsController::class, 'fbAuthToken']);

// POST to Instagram
Route::get('events/{id}/instagram-post', [\App\Http\Controllers\Api\EventInstagramController::class, 'postCarouselToInstagram'])->name('events.instagramPost');
Route::get('events/{id}/instagram-story-post', [\App\Http\Controllers\Api\EventInstagramController::class, 'postStoryToInstagram'])->name('events.instagramStoryPost');
Route::get('events/{id}/instagram-post-single', [\App\Http\Controllers\Api\EventInstagramController::class, 'postToInstagram'])->name('events.instagramPostSingle');
Route::get('events/instagram-post-week', [\App\Http\Controllers\Api\EventInstagramController::class, 'postWeekToInstagram'])->name('events.instagramPostWeek');
Route::get('events/instagram-weekend-preview', [\App\Http\Controllers\Api\EventInstagramController::class, 'postWeekendPreviewToInstagram'])->name('events.instagramWeekendPreview');

// Background job status + notifications
Route::get('job-status', [\App\Http\Controllers\JobStatusController::class, 'index'])->name('job-status.index');
Route::get('job-status/{id}', [\App\Http\Controllers\JobStatusController::class, 'show'])->name('job-status.show')->where('id', '[0-9]+');
Route::post('job-status/notifications/read', [\App\Http\Controllers\JobStatusController::class, 'markNotificationsRead'])->name('job-status.notifications.read');

Route::get('events/tag/{tag}', [\App\Http\Controllers\EventsController::class, 'indexTags'])->name('events.tag');
Route::get('events/venue/{slug}', [\App\Http\Controllers\EventsController::class, 'indexVenues'])->name('events.venue');
Route::get('events/related-to/{slug}', [\App\Http\Controllers\EventsController::class, 'indexRelatedTo'])->name('events.relatedto');
Route::get('events/type/{slug}', [\App\Http\Controllers\EventsController::class, 'indexTypes']);
Route::get('events/series/{slug}', [\App\Http\Controllers\EventsController::class, 'indexSeries']);
Route::get('events/feed', [\App\Http\Controllers\EventsController::class, 'feed']);
Route::get('events/ical', [\App\Http\Controllers\EventsController::class, 'indexIcal'])->name('events.indexIcal');
Route::get('events/feed/tag/{tag}', [\App\Http\Controllers\EventsController::class, 'feedTags']);
Route::get('events/brief-text', [\App\Http\Controllers\EventsController::class, 'briefText']);
Route::get(
    'events/export',
    [
        'as' => 'events.export',
        'uses' => '\App\Http\Controllers\EventsController@export',
    ]
);
Route::get(
    'events/export-attending',
    [
        'as' => 'events.export.attending',
        'uses' => '\App\Http\Controllers\EventsController@exportAttending',
    ]
);

Route::get('events/{id}/generate-image', [
    'as' => 'events.generateImage',
    'uses' => '\App\Http\Controllers\EventsController@generateImage',
]);

Route::get('events/{id}/import-photo', [
    'as' => 'events.importPhoto',
    'uses' => '\App\Http\Controllers\EventsController@importPhoto',
]);

Route::get('events/{id}/remind', [
    'as' => 'events.remind',
    'uses' => '\App\Http\Controllers\EventsController@remind',
]);

Route::get('events/{id}/tweet', [
    'as' => 'events.tweet',
    'uses' => '\App\Http\Controllers\EventsController@tweet',
]);

Route::get('events/{id}/attend', [
    'as' => 'events.attend',
    'uses' => '\App\Http\Controllers\EventsController@attend',
]);

Route::get('events/{id}/unattend', [
    'as' => 'events.unattend',
    'uses' => '\App\Http\Controllers\EventsController@unattend',
]);

Route::post('events/{id}/photos', [\App\Http\Controllers\EventsController::class, 'addPhoto']);
Route::delete('events/{id}/photos/{photo_id}', [\App\Http\Controllers\EventsController::class, 'deletePhoto']);

// Flyer analysis – returns extracted event data as JSON
Route::post('events/analyze-flyer', [\App\Http\Controllers\FlyerAnalysisController::class, 'analyze'])->name('events.analyzeFlyer');

//Default resource for events
Route::resource('events', \App\Http\Controllers\EventsController::class);

// FORUMS
Route::bind('forums', function ($id) {
    return Forum::whereId($id)->firstOrFail();
});

Route::get('forums/all', [\App\Http\Controllers\ForumsController::class, 'indexAll']);
Route::match(['get', 'post'], 'forums/filter', ['as' => 'forums.filter', 'uses' => '\App\Http\Controllers\ForumsController@filter']);
Route::get('forums/reset', ['as' => 'forums.reset', 'uses' => '\App\Http\Controllers\ForumsController@reset']);
Route::get('forums/rpp-reset', ['as' => 'forums.rppReset', 'uses' => '\App\Http\Controllers\ForumsController@rppReset']);
Route::resource('forums', \App\Http\Controllers\ForumsController::class);

// THREADS
Route::match(['get', 'post'], 'threads/following', [\App\Http\Controllers\ThreadsController::class, 'indexFollowing'])->name('threads.following')->middleware('auth');

Route::bind('threads', function ($id) {
    return Thread::whereId($id)->firstOrFail();
});

Route::get('threads/all', [\App\Http\Controllers\ThreadsController::class, 'indexAll']);
Route::get('threads/category/{slug}', [\App\Http\Controllers\ThreadsController::class, 'indexCategories']);
Route::get('threads/tag/{tag}', [\App\Http\Controllers\ThreadsController::class, 'indexTags'])->name('threads.tag');
Route::get('threads/series/{tag}', [\App\Http\Controllers\ThreadsController::class, 'indexSeries'])->name('threads.series');
Route::get('threads/related-to/{slug}', [\App\Http\Controllers\ThreadsController::class, 'indexRelatedTo']);
Route::post('threads/{thread}/posts', [\App\Http\Controllers\PostsController::class, 'store']);
Route::get('threads/{id}/lock', [\App\Http\Controllers\ThreadsController::class, 'lock'])->name('threads.lock');
Route::get('threads/{id}/unlock', [\App\Http\Controllers\ThreadsController::class, 'unlock'])->name('threads.unlock');

Route::match(['get', 'post'], 'threads/filter', ['as' => 'threads.filter', 'uses' => '\App\Http\Controllers\ThreadsController@filter']);
Route::get('threads/reset', ['as' => 'threads.reset', 'uses' => '\App\Http\Controllers\ThreadsController@reset']);
Route::get('threads/rpp-reset', ['as' => 'threads.rppReset', 'uses' => '\App\Http\Controllers\ThreadsController@rppReset']);

Route::get('threads/{id}/like', [
    'as' => 'threads.like',
    'uses' => '\App\Http\Controllers\ThreadsController@like',
]);

Route::get('threads/{id}/unlike', [
    'as' => 'threads.unlike',
    'uses' => '\App\Http\Controllers\ThreadsController@unlike',
]);

Route::get('threads/{id}/follow', [
    'as' => 'threads.follow',
    'uses' => '\App\Http\Controllers\ThreadsController@follow',
]);

Route::get('threads/{id}/unfollow', [
    'as' => 'threads.unfollow',
    'uses' => '\App\Http\Controllers\ThreadsController@unfollow',
]);

Route::resource('threads', \App\Http\Controllers\ThreadsController::class);

// POSTS
Route::match(['get', 'post'], 'posts/filter', ['as' => 'posts.filter', 'uses' => '\App\Http\Controllers\PostsController@filter']);
Route::get('posts/reset', ['as' => 'posts.reset', 'uses' => '\App\Http\Controllers\PostsController@reset']);
Route::get('posts/rpp-reset', ['as' => 'posts.rppReset', 'uses' => '\App\Http\Controllers\PostsController@rppReset']);
Route::get('posts/tag/{tag}', [\App\Http\Controllers\PostsController::class, 'indexTags'])->name('posts.tag');
Route::get('posts/all', [\App\Http\Controllers\PostsController::class, 'indexAll']);

Route::bind('posts', function ($id) {
    return Post::whereId($id)->firstOrFail();
});

Route::get('posts/{id}/like', [
    'as' => 'posts.like',
    'uses' => '\App\Http\Controllers\PostsController@like',
]);

Route::get('posts/{id}/unlike', [
    'as' => 'posts.unlike',
    'uses' => '\App\Http\Controllers\PostsController@unlike',
]);

Route::resource('posts', \App\Http\Controllers\PostsController::class);

// THREAD CATEGORIES
Route::get('categories/all', [\App\Http\Controllers\CategoriesController::class, 'indexAll']);
Route::match(['get', 'post'], 'categories/filter', ['as' => 'categories.filter', 'uses' => '\App\Http\Controllers\CategoriesController@filter']);
Route::get('categories/reset', ['as' => 'categories.reset', 'uses' => '\App\Http\Controllers\CategoriesController@reset']);
Route::get('categories/rpp-reset', ['as' => 'categories.rppReset', 'uses' => '\App\Http\Controllers\CategoriesController@rppReset']);

Route::bind('categories', function ($id) {
    return ThreadCategory::whereId($id)->firstOrFail();
});

Route::resource('categories', \App\Http\Controllers\CategoriesController::class);

// BLOGS
Route::get('blogs/all', [\App\Http\Controllers\BlogsController::class, 'indexAll']);
Route::match(['get', 'post'], 'blogs/filter', ['as' => 'blogs.filter', 'uses' => '\App\Http\Controllers\BlogsController@filter']);
Route::get('blogs/reset', ['as' => 'blogs.reset', 'uses' => '\App\Http\Controllers\BlogsController@reset']);
Route::get('blogs/rpp-reset', ['as' => 'blogs.rppReset', 'uses' => '\App\Http\Controllers\BlogsController@rppReset']);
Route::post('blogs/{id}/photos', [\App\Http\Controllers\BlogsController::class, 'addPhoto']);

Route::bind('blogs', function ($id) {
    return Blog::whereId($id)->firstOrFail();
});

Route::resource('blogs', \App\Http\Controllers\BlogsController::class);

// MENUS
Route::get('menus/all', [\App\Http\Controllers\MenusController::class, 'indexAll']);
Route::match(['get', 'post'], 'menus/filter', ['as' => 'menus.filter', 'uses' => '\App\Http\Controllers\MenusController@filter']);
Route::get('menus/reset', ['as' => 'menus.reset', 'uses' => '\App\Http\Controllers\MenusController@reset']);
Route::get('menus/rpp-reset', ['as' => 'menus.rppReset', 'uses' => '\App\Http\Controllers\MenusController@rppReset']);
Route::bind('menus', function ($id) {
    return Menu::whereId($id)->firstOrFail();
});
Route::resource('menus', \App\Http\Controllers\MenusController::class);
Route::get('menus/{id}/content', [\App\Http\Controllers\MenusController::class, 'content']);

// PERMISSIONS
Route::get('permissions/all', [\App\Http\Controllers\PermissionsController::class, 'indexAll']);
Route::match(['get', 'post'], 'permissions/filter', ['as' => 'permissions.filter', 'uses' => '\App\Http\Controllers\PermissionsController@filter']);
Route::get('permissions/reset', ['as' => 'permissions.reset', 'uses' => '\App\Http\Controllers\PermissionsController@reset']);
Route::get('permissions/rpp-reset', ['as' => 'permissions.rppReset', 'uses' => '\App\Http\Controllers\PermissionsController@rppReset']);

Route::bind('permissions', function ($id) {
    return Permission::whereId($id)->firstOrFail();
});

Route::resource('permissions', \App\Http\Controllers\PermissionsController::class);

// EntityTypes
Route::get('entity-types/all', [\App\Http\Controllers\EntityTypesController::class, 'indexAll']);

Route::match(['get', 'post'], 'entity-types/filter', ['as' => 'entity-types.filter', 'uses' => '\App\Http\Controllers\EntityTypesController@filter']);
Route::get('entity-types/reset', ['as' => 'entityTypes.reset', 'uses' => '\App\Http\Controllers\EntityTypesController@reset']);
Route::get('entity-types/rpp-reset', ['as' => 'entityTypes.rppReset', 'uses' => '\App\Http\Controllers\EntityTypesController@rppReset']);

Route::bind('entity-types', function ($id) {
    return EntityType::whereId($id)->firstOrFail();
});

Route::resource('entity-types', \App\Http\Controllers\EntityTypesController::class);
Route::delete('entity-types/{id}', [\App\Http\Controllers\EntityTypesController::class, 'destroy']);

// Roles
Route::match(['get', 'post'], 'roles/filter', ['as' => 'roles.filter', 'uses' => '\App\Http\Controllers\RolesController@filter']);
Route::get('roles/reset', ['as' => 'roles.reset', 'uses' => '\App\Http\Controllers\RolesController@reset']);
Route::get('roles/rpp-reset', ['as' => 'roles.rppReset', 'uses' => '\App\Http\Controllers\RolesController@rppReset']);

Route::bind('roles', function ($id) {
    return Role::whereId($id)->firstOrFail();
});

Route::resource('roles', \App\Http\Controllers\RolesController::class);
Route::delete('roles/{id}', [\App\Http\Controllers\RolesController::class, 'destroy']);

// GROUPS
Route::match(['get', 'post'], 'groups/filter', ['as' => 'groups.filter', 'uses' => '\App\Http\Controllers\GroupsController@filter']);
Route::get('groups/all', [\App\Http\Controllers\GroupsController::class, 'indexAll']);

Route::get('groups/reset', ['as' => 'groups.reset', 'uses' => '\App\Http\Controllers\GroupsController@reset']);
Route::get('groups/rpp-reset', ['as' => 'groups.rppReset', 'uses' => '\App\Http\Controllers\GroupsController@rppReset']);

Route::bind('groups', function ($id) {
    return Group::whereId($id)->firstOrFail();
});

Route::resource('groups', \App\Http\Controllers\GroupsController::class);

// ENTITIES
Route::match(['get', 'post'], 'entities/following', [\App\Http\Controllers\EntitiesController::class, 'indexFollowing'])->name('entities.following')->middleware('auth');

Route::get('entities/{id}/load-embeds', [\App\Http\Controllers\EntitiesController::class, 'loadEmbeds']);
Route::get('entities/{id}/load-minimal-embeds', [\App\Http\Controllers\EntitiesController::class, 'loadMinimalEmbeds']);
Route::get('entities/{slug}/minimal-embeds', [\App\Http\Controllers\EntitiesController::class, 'loadMinimalEmbedsBySlug']);
Route::get('entities/{slug}/embeds', [\App\Http\Controllers\EntitiesController::class, 'loadEmbedsBySlug']);
Route::post('entities/{id}/photos', [\App\Http\Controllers\EntitiesController::class, 'addPhoto']);

Route::get('entities/type/{type}', [\App\Http\Controllers\EntitiesController::class, 'indexTypes']);

Route::get('entities/role/{role}', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->name('entities.role');

Route::match(['get', 'post'], 'entities/filter', ['as' => 'entities.filter', 'uses' => '\App\Http\Controllers\EntitiesController@filter']);
Route::get('entities/apply-filter', ['as' => 'entities.applyFilterFromUrl', 'uses' => '\App\Http\Controllers\EntitiesController@applyFilterFromUrl']);
Route::get('entities/reset', ['as' => 'entities.reset', 'uses' => '\App\Http\Controllers\EntitiesController@reset']);
Route::get('entities/rpp-reset', ['as' => 'entities.rppReset', 'uses' => '\App\Http\Controllers\EntitiesController@rppReset']);

Route::post('entities/quick-store', [\App\Http\Controllers\EntitiesController::class, 'quickStore'])->name('entities.quickStore')->middleware('auth');
Route::get('entities/quick-check', [\App\Http\Controllers\EntitiesController::class, 'quickCheck'])->name('entities.quickCheck')->middleware('auth');

Route::get('entities/tag/{tag}', [\App\Http\Controllers\EntitiesController::class, 'indexTags'])->name('entities.tag');
Route::get('entities/alias/{alias}', [\App\Http\Controllers\EntitiesController::class, 'indexAliases'])->name('entities.alias');
Route::get('entities/slug/{slug}', [\App\Http\Controllers\EntitiesController::class, 'indexSlug'])->name('entities.slug');

Route::get('entities/{id}/tweet', [
    'as' => 'entities.tweet',
    'uses' => '\App\Http\Controllers\EntitiesController@tweet',
]);

Route::get('entities/{id}/instagram-post', [\App\Http\Controllers\EntitiesController::class, 'postToInstagram'])->name('entities.instagramPost');
Route::get('entities/{id}/instagram-story-post', [\App\Http\Controllers\EntitiesController::class, 'postStoryToInstagram'])->name('entities.instagramStoryPost');
Route::get('entities/{id}/send-update-summary', [\App\Http\Controllers\EntitiesController::class, 'sendUpdateSummary'])->name('entities.sendUpdateSummary')->middleware('auth');

Route::match(['get', 'post'], 'entities/{id}/follow', [
    'as' => 'entities.follow',
    'uses' => '\App\Http\Controllers\EntitiesController@follow',
]);

Route::get('entities/{id}/unfollow', [
    'as' => 'entities.unfollow',
    'uses' => '\App\Http\Controllers\EntitiesController@unfollow',
]);

Route::bind('entities', function ($id) {
    return Entity::whereId($id)->firstOrFail();
});

Route::resource('entities', \App\Http\Controllers\EntitiesController::class);

Route::bind('locations', function ($id) {
    return Location::whereId($id)->firstOrFail();
});

Route::resource('entities.locations', \App\Http\Controllers\LocationsController::class);

Route::bind('contacts', function ($id) {
    return Contact::whereId($id)->firstOrFail();
});

Route::get('/entities/{entity:slug}/contacts/{contact:id}/create', [\App\Http\Controllers\ContactsController::class, 'create']);
Route::get('/entities/{entity:slug}/contacts/{contact:id}/edit', [\App\Http\Controllers\ContactsController::class, 'edit']);
Route::post('/entities/{entity:slug}/contacts/{contact:id}/update', [\App\Http\Controllers\ContactsController::class, 'update']);
Route::resource('entities.contacts', \App\Http\Controllers\ContactsController::class);

Route::bind('links', function ($id) {
    return Link::whereId($id)->firstOrFail();
});

Route::resource('entities.links', \App\Http\Controllers\LinksController::class);

Route::bind('comments', function ($id) {
    return Comment::whereId($id)->firstOrFail();
});

Route::get('/entities/{entity:slug}/comments/{comment:id}/edit', [\App\Http\Controllers\CommentsController::class, 'edit']);
Route::delete('/entities/{entity:slug}/comments/{comment:id}/edit', [\App\Http\Controllers\CommentsController::class, 'destroy']);

Route::resource('entities.comments', \App\Http\Controllers\CommentsController::class);
Route::resource('events.comments', \App\Http\Controllers\CommentsController::class);
Route::resource('events.reviews', \App\Http\Controllers\EventReviewsController::class);

// REVIEWS
Route::match(['get', 'post'], 'reviews/filter', ['as' => 'reviews.filter', 'uses' => '\App\Http\Controllers\ReviewsController@filter']);
Route::get('reviews/filter', ['as' => 'reviews.filter', 'uses' => '\App\Http\Controllers\ReviewsController@filter']);
Route::get('reviews/reset', ['as' => 'reviews.reset', 'uses' => '\App\Http\Controllers\ReviewsController@reset']);
Route::get('reviews/rpp-reset', ['as' => 'reviews.rppReset', 'uses' => '\App\Http\Controllers\ReviewsController@rppReset']);
Route::resource('reviews', \App\Http\Controllers\ReviewsController::class);

// SERIES
Route::get('series/{id}/load-embeds', [\App\Http\Controllers\SeriesController::class, 'loadEmbeds']);
Route::get('series/{id}/load-minimal-embeds', [\App\Http\Controllers\SeriesController::class, 'loadMinimalEmbeds']);
Route::get('series/{slug}/minimal-embeds', [\App\Http\Controllers\SeriesController::class, 'loadMinimalEmbedsBySlug']);
Route::get('series/{slug}/embeds', [\App\Http\Controllers\SeriesController::class, 'loadEmbedsBySlug']);
Route::match(['get', 'post'], 'series/following', [\App\Http\Controllers\SeriesController::class, 'indexFollowing'])->name('series.following')->middleware('auth');

Route::get('series/createOccurrence', [
    'as' => 'series.createOccurrence',
    'uses' => '\App\Http\Controllers\SeriesController@createOccurrence',
]);

Route::get('series/type/{type}', [\App\Http\Controllers\SeriesController::class, 'indexTypes']);
Route::get('series/tag/{tag}', [\App\Http\Controllers\SeriesController::class, 'indexTags'])->name('series.tag');
Route::get('series/related-to/{slug}', [\App\Http\Controllers\SeriesController::class, 'indexRelatedTo']);
Route::get('series/week', [\App\Http\Controllers\SeriesController::class, 'indexWeek']);
Route::get('series/cancelled', [\App\Http\Controllers\SeriesController::class, 'indexCancelled'])->name('series.cancelled');
Route::post('series/{id}/photos', [\App\Http\Controllers\SeriesController::class, 'addPhoto']);
Route::delete('series/{id}/photos/{photo_id}', [\App\Http\Controllers\SeriesController::class, 'deletePhoto']);
Route::get(
    'series/export',
    [
        'as' => 'series.export',
        'uses' => '\App\Http\Controllers\SeriesController@export',
    ]
);
Route::match(['get', 'post'], 'series/filter', ['as' => 'series.filter', 'uses' => '\App\Http\Controllers\SeriesController@filter']);
Route::get('series/apply-filter', ['as' => 'series.applyFilterFromUrl', 'uses' => '\App\Http\Controllers\SeriesController@applyFilterFromUrl']);
Route::get('series/reset', ['as' => 'series.reset', 'uses' => '\App\Http\Controllers\SeriesController@reset']);
Route::get('series/rpp-reset', ['as' => 'series.rppReset', 'uses' => '\App\Http\Controllers\SeriesController@rppReset']);

Route::get('series/{id}/follow', [
    'as' => 'series.follow',
    'uses' => '\App\Http\Controllers\SeriesController@follow',
]);

Route::get('series/{id}/unfollow', [
    'as' => 'series.unfollow',
    'uses' => '\App\Http\Controllers\SeriesController@unfollow',
]);


Route::resource('series', \App\Http\Controllers\SeriesController::class);

Route::get('series/{series:slug}', [\App\Http\Controllers\SeriesController::class, 'show'])->name('series.show');


Route::get('tags/create', [\App\Http\Controllers\TagsController::class, 'create'])->name('tags.create');
Route::get('tags/{tag}', [\App\Http\Controllers\TagsController::class, 'show'])->name('tags.show');
Route::get('tags/{tag}/edit', [\App\Http\Controllers\TagsController::class, 'edit'])->name('tags.edit');

Route::get('tags/{id}/follow', [
    'as' => 'tags.follow',
    'uses' => '\App\Http\Controllers\TagsController@follow',
]);

Route::get('tags/{id}/unfollow', [
    'as' => 'tags.unfollow',
    'uses' => '\App\Http\Controllers\TagsController@unfollow',
]);

Route::resource('tags', \App\Http\Controllers\TagsController::class);

// Add the route for rss
Route::get('rss', [\App\Http\Controllers\EventsController::class, 'rss']);
Route::get('rss/tag/{tag}', [\App\Http\Controllers\EventsController::class, 'rssTags']);

// Semantic alternate routes for entities by role
// These routes provide cleaner URLs for common entity types
// Index routes: /venue, /artist, /dj, etc. - lists entities by role
Route::get('venue', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'venue')->name('venue.index');
Route::get('artist', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'artist')->name('artist.index');
Route::get('dj', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'dj')->name('dj.index');
Route::get('producer', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'producer')->name('producer.index');
Route::get('promoter', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'promoter')->name('promoter.index');
Route::get('shop', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'shop')->name('shop.index');
Route::get('band', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'band')->name('band.index');
Route::get('visualist', [\App\Http\Controllers\EntitiesController::class, 'indexRoles'])->defaults('role', 'visualist')->name('visualist.index');

// Detail routes: /venue/{slug}, /artist/{slug}, etc. - shows specific entity
Route::get('venue/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'venue')->name('venue.show');
Route::get('artist/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'artist')->name('artist.show');
Route::get('dj/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'dj')->name('dj.show');
Route::get('producer/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'producer')->name('producer.show');
Route::get('promoter/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'promoter')->name('promoter.show');
Route::get('shop/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'shop')->name('shop.show');
Route::get('band/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'band')->name('band.show');
Route::get('visualist/{slug}', [\App\Http\Controllers\EntitiesController::class, 'showByRoleAndSlug'])->defaults('role', 'visualist')->name('visualist.show');

// Click tracking for event and series ticket links
Route::get('go/evt-{id}', [\App\Http\Controllers\ClickTrackController::class, 'redirectEvent'])->name('clicktrack.event')->where('id', '[0-9]+');
Route::get('go/ser-{id}', [\App\Http\Controllers\ClickTrackController::class, 'redirectSeries'])->name('clicktrack.series')->where('id', '[0-9]+');

// Short URLs – create a short URL and resolve it
Route::post('short-url', [\App\Http\Controllers\ShortUrlController::class, 'shorten'])->name('short-url.shorten');
Route::get('s/{code}', [\App\Http\Controllers\ShortUrlController::class, 'redirect'])->name('short-url.redirect')->where('code', '[a-zA-Z0-9]+');
