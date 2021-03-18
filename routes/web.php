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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Events\EventCreated;
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
use App\Models\Post;
use App\Models\Series;
use App\Models\Thread;
use App\Models\User;

Auth::routes(['verify' => true]);

Route::get('/redirect', 'SocialAuthFacebookController@redirect');
Route::get('/callback', 'SocialAuthFacebookController@callback');

// Authentication Routes...
$this->get('login', 'Auth\LoginController@showLoginForm')->name('login');
$this->post('login', 'Auth\LoginController@login');
$this->post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
$this->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
$this->post('register', 'Auth\RegisterController@register');

// Password Reset Routes...
$this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.forgot');
$this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
$this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
$this->post('password/reset', 'Auth\ResetPasswordController@reset');

Route::get('/', 'PagesController@home')->name('home');
Route::get('/home', 'PagesController@home')->name('home');

Route::get('about', 'PagesController@about');
Route::get('privacy', 'PagesController@privacy');
Route::get('tos', 'PagesController@tos');

Route::get('help', 'PagesController@help');

Route::get('calendar', 'EventsController@calendar')->name('calendar');
Route::get('calendar/tag/{tag}', 'EventsController@calendarTags')->name('calendar.tag');
Route::get('calendar/relatedto/{slug}', 'EventsController@calendarRelatedTo');
Route::get('calendar/free', 'EventsController@calendarFree')->name('calendar.free');
Route::get('calendar/attending', 'EventsController@calendarAttending')->name('calendar.attending');
Route::get('calendar/type/{tag}', 'EventsController@calendarEventTypes')->name('calendar.type');
Route::get('calendar/min_age/{age}', 'EventsController@calendarMinAge')->name('calendar.minAge');

Route::get('search', 'PagesController@search');

Route::get('activity', 'PagesController@activity')->name('pages.activity');

Route::get('activity/filter', ['as' => 'activity.filter', 'uses' => 'PagesController@filter']);
Route::get('activity/reset', ['as' => 'activity.reset', 'uses' => 'PagesController@reset']);
Route::get('activity/rpp-reset', ['as' => 'activity.rppResetActivity', 'uses' => 'PagesController@rppResetActivity']);

Route::get('tools', 'PagesController@tools')->name('pages.tools');
Route::post('invite', 'PagesController@invite')->name('pages.invite');

Route::get('events/importPhotos', [
    'as' => 'events.importPhotos',
    'uses' => 'EventsController@importPhotos'
]);

Route::bind('users', function ($id) {
    return App\Models\User::whereId($id)->firstOrFail();
});

Route::get('impersonate/{user}', function (User $user) {
    Auth::login($user);

    return redirect('/');
})->middleware('can:admin')->name('user.impersonate');

Route::post('users/{id}/photos', 'UsersController@addPhoto');
Route::delete('users/{id}/photos/{photo_id}', 'UsersController@deletePhoto');

Route::get('users/{id}/activate', [
    'as' => 'users.activate',
    'uses' => 'UsersController@activate'
]);

Route::get('users/{id}/reminder', [
    'as' => 'users.reminder',
    'uses' => 'UsersController@reminder'
]);

Route::get('users/{id}/weekly', [
    'as' => 'users.weekly',
    'uses' => 'UsersController@weekly'
]);

Route::get('users/{id}/suspend', [
    'as' => 'users.suspend',
    'uses' => 'UsersController@suspend'
]);

Route::get('users/{id}/ical', [
    'as' => 'users.ical',
    'uses' => 'UsersController@ical'
]);

Route::get('users/{id}/delete', [
    'as' => 'users.delete',
    'uses' => 'UsersController@delete'
]);

Route::match(['get', 'post'], 'users/filter', ['as' => 'users.filter', 'uses' => 'UsersController@filter']);
Route::get('users/reset', ['as' => 'users.reset', 'uses' => 'UsersController@reset']);
Route::get('users/rpp-reset', ['as' => 'users.rppReset', 'uses' => 'UsersController@rppReset']);

Route::resource('users', 'UsersController');

Route::get('profile/{id}', 'UsersController@show')->name('users.profile');
// PHOTOS

Route::delete('photos/{id}', 'PhotosController@destroy');
Route::post('photos/{id}/setPrimary', 'PhotosController@setPrimary');
Route::post('photos/{id}/unsetPrimary', 'PhotosController@unsetPrimary');

// EVENTS
Route::get('events/createSeries', [
    'as' => 'events.createSeries',
    'uses' => 'EventsController@createSeries'
]);
Route::get('events/createThread', [
    'as' => 'events.createThread',
    'uses' => 'EventsController@createThread'
]);
Route::get('events/dispatch', function () {
    EventUpdated::dispatch();

    return 'test';
});
Route::get('update', function () {
    EventUpdated::dispatch(new Event());
});

Route::get('events/today', 'EventsController@indexToday');
Route::match(['get', 'post'], 'events/grid', 'EventsController@indexGrid')->name('events.grid');
Route::get('events/timeline', 'EventsController@indexTimeline')->name('events.timeline');
Route::get('events/future', 'EventsController@indexFuture')->name('events.future');
Route::get('events/past', 'EventsController@indexPast');
Route::get('events/week', 'EventsController@indexWeek')->name('events.week');
Route::get('events/starting/{date}', 'EventsController@indexStarting');
Route::get('events/by-date/{year}/{month?}/{day?}', 'EventsController@indexByDate')
    ->where('year', '[1-9][0-9][0-9][0-9]')
    ->where('month', '(0?[1-9]|1[012])$');
Route::get('events/daily', 'EventsController@daily');
Route::get('events/day/{day}', 'EventsController@day')->name('events.day');
Route::match(['get', 'post'], 'events/attending', 'EventsController@indexAttending')->name('events.attending');
Route::match(['get', 'post'], 'events/filter', ['as' => 'events.filter', 'uses' => 'EventsController@filter']);
Route::get('events/reset', ['as' => 'events.reset', 'uses' => 'EventsController@reset']);
Route::get('events/rpp-reset', ['as' => 'events.rppReset', 'uses' => 'EventsController@rppReset']);

Route::get('events/tag/{tag}', 'EventsController@indexTags')->name('events.tag');
Route::get('events/venue/{slug}', 'EventsController@indexVenues')->name('events.venue');
Route::get('events/relatedto/{slug}', 'EventsController@indexRelatedTo')->name('events.relatedto');
Route::get('events/type/{slug}', 'EventsController@indexTypes');
Route::get('events/series/{slug}', 'EventsController@indexSeries');
Route::get('events/feed', 'EventsController@feed');
Route::get('events/feed/tag/{tag}', 'EventsController@feedTags');
Route::get(
    'events/export',
    [
        'as' => 'events.export',
        'uses' => 'EventsController@export'
    ]
);

Route::get('events/{id}/importPhoto', [
    'as' => 'events.importPhoto',
    'uses' => 'EventsController@importPhoto'
]);

Route::get('events/{id}/createThread', [
    'as' => 'events.createThread',
    'uses' => 'EventsController@createThread'
]);

Route::get('events/{id}/remind', [
    'as' => 'events.remind',
    'uses' => 'EventsController@remind'
]);

Route::get('events/{id}/tweet', [
    'as' => 'events.tweet',
    'uses' => 'EventsController@tweet'
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

Route::bind('events', function ($id) {
    return Event::whereId($id)->firstOrFail();
});

Route::resource('events', 'EventsController');

// FORUMS
Route::bind('forums', function ($id) {
    return Forum::whereId($id)->firstOrFail();
});

Route::get('forums/all', 'ForumsController@indexAll');

Route::resource('forums', 'ForumsController');

// THREADS
Route::bind('threads', function ($id) {
    return Thread::whereId($id)->firstOrFail();
});

Route::get('threads/all', 'ThreadsController@indexAll');
Route::get('threads/category/{slug}', 'ThreadsController@indexCategories');
Route::get('threads/tag/{tag}', 'ThreadsController@indexTags')->name('threads.tag');
Route::get('threads/series/{tag}', 'ThreadsController@indexSeries');
Route::get('threads/relatedto/{slug}', 'ThreadsController@indexRelatedTo');
Route::post('threads/{thread}/posts', 'PostsController@store');
Route::get('threads/{id}/lock', 'ThreadsController@lock')->name('threads.lock');
Route::get('threads/{id}/unlock', 'ThreadsController@unlock')->name('threads.unlock');

Route::match(['get', 'post'], 'threads/filter', ['as' => 'threads.filter', 'uses' => 'ThreadsController@filter']);
Route::get('threads/reset', ['as' => 'threads.reset', 'uses' => 'ThreadsController@reset']);
Route::get('threads/rpp-reset', ['as' => 'threads.rppReset', 'uses' => 'ThreadsController@rppReset']);

Route::get('threads/{id}/like', [
    'as' => 'threads.like',
    'uses' => 'ThreadsController@like'
]);

Route::get('threads/{id}/unlike', [
    'as' => 'threads.unlike',
    'uses' => 'ThreadsController@unlike'
]);

Route::get('threads/{id}/follow', [
    'as' => 'threads.follow',
    'uses' => 'ThreadsController@follow'
]);

Route::get('threads/{id}/unfollow', [
    'as' => 'threads.unfollow',
    'uses' => 'ThreadsController@unfollow'
]);

Route::resource('threads', 'ThreadsController');

// POSTS
Route::get('posts/all', 'PostsController@indexAll');

Route::bind('posts', function ($id) {
    return Post::whereId($id)->firstOrFail();
});

Route::get('posts/{id}/like', [
    'as' => 'posts.like',
    'uses' => 'PostsController@like'
]);

Route::get('posts/{id}/unlike', [
    'as' => 'posts.unlike',
    'uses' => 'PostsController@unlike'
]);
Route::resource('posts', 'PostsController');
Route::get('posts/tag/{tag}', 'PostsController@indexTags')->name('posts.tag');

// BLOGS
Route::get('blogs/all', 'BlogsController@indexAll');

Route::bind('blogs', function ($id) {
    return Blog::whereId($id)->firstOrFail();
});

Route::resource('blogs', 'BlogsController');

// MENUS
Route::get('menus/all', 'MenusController@indexAll');

Route::bind('menus', function ($id) {
    return Menu::whereId($id)->firstOrFail();
});
Route::resource('menus', 'MenusController');
Route::get('menus/{id}/content', 'MenusController@content');

// PERMISSIONS
Route::get('permissions/all', 'PermissionsController@indexAll');

Route::bind('permissions', function ($id) {
    return Permission::whereId($id)->firstOrFail();
});

Route::resource('permissions', 'PermissionsController');

// EntityTypes
Route::get('entity-types/all', 'EntityTypesController@indexAll');

Route::bind('entity-types', function ($id) {
    return EntityType::whereId($id)->firstOrFail();
});

Route::resource('entity-types', 'EntityTypesController');
Route::delete('entity-types/{id}', 'EntityTypesController@destroy');

// GROUPS
Route::get('groups/all', 'GroupsController@indexAll');

Route::bind('groups', function ($id) {
    return Group::whereId($id)->firstOrFail();
});

Route::resource('groups', 'GroupsController');

// ENTITIES
Route::post('entities/{id}/photos', 'EntitiesController@addPhoto');

Route::get('entities/type/{type}', 'EntitiesController@indexTypes');

Route::get('entities/role/{role}', 'EntitiesController@indexRoles')->name('entities.role');

Route::match(['get', 'post'], 'entities/filter', ['as' => 'entities.filter', 'uses' => 'EntitiesController@filter']);
Route::get('entities/reset', ['as' => 'entities.reset', 'uses' => 'EntitiesController@reset']);
Route::get('entities/rpp-reset', ['as' => 'entities.rppReset', 'uses' => 'EntitiesController@rppReset']);

Route::get('entities/tag/{tag}', 'EntitiesController@indexTags')->name('entities.tag');
Route::get('entities/alias/{alias}', 'EntitiesController@indexAliases')->name('entities.alias');
Route::get('entities/slug/{slug}', 'EntitiesController@indexSlug')->name('entities.slug');

Route::get('entities/{id}/follow', [
    'as' => 'entities.follow',
    'uses' => 'EntitiesController@follow'
]);

Route::post('entities/{id}/follow', [
    'as' => 'entities.follow',
    'uses' => 'EntitiesController@follow'
]);

Route::get('entities/{id}/unfollow', [
    'as' => 'entities.unfollow',
    'uses' => 'EntitiesController@unfollow'
]);

Route::bind('entities', function ($id) {
    return Entity::whereId($id)->firstOrFail();
});

Route::resource('entities', 'EntitiesController');

Route::bind('locations', function ($id) {
    return Location::whereId($id)->firstOrFail();
});

Route::resource('entities.locations', 'LocationsController');

Route::bind('contacts', function ($id) {
    return Contact::whereId($id)->firstOrFail();
});

Route::resource('entities.contacts', 'ContactsController');

Route::bind('links', function ($id) {
    return Link::whereId($id)->firstOrFail();
});

Route::resource('entities.links', 'LinksController');

Route::bind('comments', function ($id) {
    return Comment::whereId($id)->firstOrFail();
});

Route::resource('entities.comments', 'CommentsController');
Route::resource('events.comments', 'CommentsController');

Route::resource('events.reviews', 'EventReviewsController');

// REVIEWS
Route::resource('reviews', 'ReviewsController');
Route::get('reviews/filter', ['as' => 'reviews.filter', 'uses' => 'ReviewsController@filter']);
Route::get('reviews/reset', ['as' => 'reviews.reset', 'uses' => 'ReviewsController@reset']);

// SERIES
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
Route::get(
    'series/export',
    [
        'as' => 'series.export',
        'uses' => 'SeriesController@export'
    ]
);
Route::match(['get', 'post'], 'series/filter', ['as' => 'series.filter', 'uses' => 'SeriesController@filter']);
Route::get('series/reset', ['as' => 'series.reset', 'uses' => 'SeriesController@reset']);
Route::get('series/rpp-reset', ['as' => 'series.rppReset', 'uses' => 'SeriesController@rppReset']);

Route::get('series/{id}/follow', [
    'as' => 'series.follow',
    'uses' => 'SeriesController@follow'
]);

Route::get('series/{id}/unfollow', [
    'as' => 'series.unfollow',
    'uses' => 'SeriesController@unfollow'
]);

Route::bind('series', function ($id) {
    return Series::whereId($id)->firstOrFail();
});

Route::resource('series', 'SeriesController');

Route::get('tags/create', 'TagsController@create')->name('tags.create');
Route::get('tags/{tag}', 'TagsController@indexTags')->name('tags.show');
Route::get('tags/{tag}/edit', 'TagsController@edit')->name('tags.edit');

Route::get('tags/{id}/follow', [
    'as' => 'tags.follow',
    'uses' => 'TagsController@follow'
]);

Route::get('tags/{id}/unfollow', [
    'as' => 'tags.unfollow',
    'uses' => 'TagsController@unfollow'
]);

Route::resource('tags', 'TagsController');

// Add the route for rss
Route::get('rss', 'EventsController@rss');
Route::get('rss/tag/{tag}', 'EventsController@rssTags');
