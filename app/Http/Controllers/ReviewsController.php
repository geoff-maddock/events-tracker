<?php

namespace App\Http\Controllers;

use App\Filters\ReviewFilters;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\ReviewType;
use App\Models\Thread;
use App\Traits\Followable;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\EventReview;
use App\Models\Event;
use App\Models\Entity;
use App\Models\Tag;
use App\Models\Activity;
use App\Models\TagType;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Database\Eloquent\Builder;

class ReviewsController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

    protected bool $hasFilter;

    protected Event $event;

    protected array $filters;

    protected ReviewFilters $filter;

    public function __construct(Event $event, ReviewFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->event = $event;
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.reviews.';

        // default list variables
        $this->defaultSort = 'event_reviews.created_at';
        $this->defaultSortDirection = 'desc';
        $this->defaultLimit = 10;

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;

        $this->defaultSortCriteria = ['event_reviews.created_at', 'desc'];
        $this->hasFilter = false;

        $this->hasFilter = 0;
        parent::__construct();
    }

    /**
     * Update the filters parameters from the request
     *
     */
    protected function updateFilters($request)
    {
        $filters = [];

        if (!empty($request->input('filter_name'))) {
            $filters['filter_name'] = $request->input('filter_name');
        };

        if (!empty($request->input('filter_venue'))) {
            $filters['filter_venue'] = $request->input('filter_venue');
        };

        if (!empty($request->input('filter_tag'))) {
            $filters['filter_tag'] = $request->input('filter_tag');
        };

        if (!empty($request->input('filter_related'))) {
            $filters['filter_related'] = $request->input('filter_related');
        };

        // save filters to session
        $this->setFilters($request, $filters);
    }

    /**
     * Get the default sort array
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return ['id', 'desc'];
    }

    /**
     * Get the default filters array
     *
     * @return array
     */
    public function getDefaultFilters()
    {
        return [];
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return View
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        $listParamSessionStore->setBaseIndex('internal_review');
        $listParamSessionStore->setKeyPrefix('internal_review_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ReviewsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = EventReview::query()->select('event_reviews.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort([$this->defaultSort => $this->defaultSortDirection]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the blogs
        $reviews = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('reviews.index')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters()
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
            ->with(compact('reviews'))
            ->render();
    }

    /**
     * Display filter
     *
     * @param Request $request
     * @return View
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ) {
        $listParamSessionStore->setBaseIndex('internal_review');
        $listParamSessionStore->setKeyPrefix('internal_review_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ReviewsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = EventReview::query()->select('event_reviews.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort([$this->defaultSort => $this->defaultSortDirection]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // query and paginate the blogs
        $reviews = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('reviews.index')
        ->with(array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters()
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions()
        ))
            ->with(compact('reviews'))
            ->render();
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function indexAll(Request $request)
    {
        $this->hasFilter = 1;

        // updates sort, limit from request
        $this->updatePaging($request);

        $future_events = EventReview::future()->paginate(100000);
        $future_events->filter(function ($e) {
            return (($e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        $past_events = EventReview::past()->paginate(100000);
        $past_events->filter(function ($e) {
            return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('reviews.index')
            ->with(['limit' => $this->limit, 'sortBy' => $this->sort, 'sortOrder' => $this->sortDirection, 'hasFilter' => $this->hasFilter])
            ->with(compact('future_events'))
            ->with(compact('past_events'));
    }

    /**
     * Display a listing of the resource.
     */
    public function indexPast(Request $request)
    {
        $this->hasFilter = 1;

        // updates sort, limit from request
        $this->updatePaging($request);

        $this->limit = 10;

        $past_events = EventReview::past()->paginate($this->limit);
        $past_events->filter(function ($e) {
            return (($e->visibility && $e->visibility->name == 'Public') || ($this->user && $e->created_by == $this->user->id));
        });

        return view('reviews.index')
            ->with(['limit' => $this->limit, 'sortBy' => $this->sort, 'sortOrder' => $this->sortDirection, 'hasFilter' => $this->hasFilter])
            ->with(compact('past_events'));
    }

    /**
     * Reset the filtering of reviews
     *
     * @throws \Throwable
     */
    public function reset(Request $request)
    {
        // doesn't have filter, but temp
        $this->hasFilter = false;

        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        // base criteria
        $query = $this->buildCriteria($request);

        // updates sort, limit from request
        $this->updatePaging($request);

        // get future events
        $reviews = $query->paginate($this->limit);

        if ($redirect = $request->input('redirect')) {
            return redirect()->route($redirect);
        };

        return view('reviews.index')
            ->with(['limit' => $this->limit, 'sortBy' => $this->sort, 'sortOrder' => $this->sortDirection, 'hasFilter' => $this->hasFilter])
            ->with(compact('reviews'))
            ->render();
    }

    /**
     * Show a form to create a new review
     *
     * @return view
     **/
    public function create()
    {
        // get a list of venues
        $venues = ['' => ''] + Entity::getVenues()->pluck('name', 'id')->all();

        // get a list of promoters
        $promoters = ['' => ''] + Entity::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $reviewTypes = ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $visibilities = ['' => ''] + Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $events = Event::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('reviews.create', compact('reviewTypes', 'visibilities', 'tags', 'events'));
    }

    public function show(EventReview $review)
    {
        return view('reviews.show', compact('review'));
    }

    public function store(EventRequest $request, Event $event)
    {
        $msg = '';

        // get the request
        $input = $request->all();

        // validate - hmm, isn't this doing it elsewhere?

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag;
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            };
        }

        $event = $event->create($input);

        $event->tags()->attach($syncArray);
        $event->entities()->attach($request->input('entity_list'));

        // here, make a call to notify all users who are following any of the sync'd tags
        $this->notifyFollowing($event);

        // add to activity log
        Activity::log($event, $this->user, 1);

        flash()->success('Success', 'Your event has been created');

        return redirect()->route('reviews.index');
    }

    /**
     * @param Event $event
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing(Event $event)
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $event->tags()->get();
        $users = [];

        // improve this so it will only sent one email to each user per event, and include a list of all tags they were following that led to the notification
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $event, $tag, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $tag->name . ' :: ' . $event->start_at->format('D F jS') . ' ' . $event->name);
                    });
                    $users[$user->id] = $tag->name;
                };
            };
        };

        // notify users following any of the entities
        $entities = $event->entities()->get();

        // improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
        foreach ($entities as $entity) {
            foreach ($entity->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $entity, 'reply_email' => $reply_email, 'site' => $site], function ($m) use ($user, $event, $entity, $reply_email, $site) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $entity->name . ' :: ' . $event->start_at->format('D F jS') . ' ' . $event->name);
                    });
                    $users[$user->id] = $entity->name;
                };
            };
        };

        return back();
    }

    protected function unauthorized(EventRequest $request)
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function edit(EventReview $review)
    {
        $this->middleware('auth');

        // moved necessary lists into AppServiceProvider
        return view('reviews.edit', compact('review'))
            ->with($this->getFormOptions());
    }

    public function update(Event $event, EventRequest $request)
    {
        $msg = '';

        $event->fill($request->input())->save();

        if (!$event->ownedBy($this->user)) {
            $this->unauthorized($request);
        };

        $tagArray = $request->input('tag_list', []);
        $syncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag;
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            };
        }

        $event->tags()->sync($syncArray);
        $event->entities()->sync($request->input('entity_list', []));

        // add to activity log
        Activity::log($event, $this->user, 2);

        flash()->success('Success', 'Your event has been updated');

        return redirect('events');
    }

    public function destroy(Event $event)
    {
        // add to activity log
        Activity::log($event, $this->user, 3);

        $event->delete();

        flash()->success('Success', 'Your event has been deleted!');

        return redirect('events');
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['review' => 'Review', 'created_at' => 'Created At', 'review_type_id' => 'Review Type', 'rating' => 'Rating'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
            'visibilityOptions' => ['' => '&nbsp;'] + Visibility::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'sortOrderOptions' => ['' => '', 'asc' => 'asc', 'desc' => 'desc'],
            'visibilityOptions' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
            'reviewTypeOptions' => ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all()
        ];
    }
}
