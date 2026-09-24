<?php

namespace App\Http\Controllers;

use App\Filters\ReviewFilters;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\ReviewType;
use App\Models\User;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $this->hasFilter = false;
        parent::__construct();
    }

    /**
     * Get the default sort array.
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return ['id', 'desc'];
    }

    /**
     * Get the default filters array.
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
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
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

        return view('reviews.index-tw')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('reviews'))
            ->render();
    }

    /**
     * Display filter.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
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

        return view('reviews.index-tw')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('reviews'))
            ->render();
    }

    /**
     * Reset the rpp, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_review_index';
        $listParamSessionStore->setBaseIndex('internal_review');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('reviews.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_review_index';
        $listParamSessionStore->setBaseIndex('internal_review');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($this->resolveRedirectRoute($request, 'reviews.index'));
    }

    public function show(EventReview $review): View
    {
        return view('reviews.show-tw', compact('review'));
    }

    public function edit(EventReview $review): View
    {
        // the author, or an admin (same rule as events.reviews.destroy)
        abort_unless($this->user && ($review->ownedBy($this->user) || $this->user->isAdmin()), 403);

        // moved necessary lists into AppServiceProvider
        return view('reviews.edit-tw', compact('review'))
            ->with($this->getFormOptions());
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['review' => 'Review', 'created_at' => 'Created At', 'review_type_id' => 'Review Type', 'rating' => 'Rating'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'visibilityOptions' => ['' => '&nbsp;'] + Visibility::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'reviewTypeOptions' => ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userOptions' => ['' => '&nbsp;'] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'visibilityOptions' => ['' => ''] + Visibility::pluck('name', 'id')->all(),
            'reviewTypeOptions' => ['' => ''] + ReviewType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }
}
