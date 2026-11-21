<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
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
    use BuildsListPages;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    // array of sort criteria to be applied in order
    protected array $defaultSortCriteria;

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

        parent::__construct();
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
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_review',
            'internal_review_index',
            action([ReviewsController::class, 'index']),
            $this->filter,
            EventReview::query()->whereHas('event', Event::visibleTo($this->user))->select('event_reviews.*'),
            [$this->defaultSort => $this->defaultSortDirection],
        );

        $reviews = $listResultSet->getList()->paginate($listResultSet->getLimit());

        return view('reviews.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
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
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
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
        $this->clearList($listParamSessionStore, 'internal_review', $request->get('key') ?? 'internal_review_index', filters: false);

        return redirect()->route('reviews.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_review', $request->get('key') ?? 'internal_review_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'reviews.index'));
    }

    public function show(EventReview $review): View
    {
        // a review is only as visible as its event, as in index()
        $event = Event::find($review->event_id);
        abort_unless($event && $event->isVisibleTo($this->user), 404);

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
