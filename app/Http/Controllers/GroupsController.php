<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\GroupFilters;
use App\Http\Requests\GroupRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupsController extends Controller
{
    use BuildsListPages;

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected GroupFilters $filter;

    public function __construct(GroupFilters $filter)
    {
        // group and permission changes grant privileges, so every write is admin-only
        // listing them shows who the admins are, so reading is admin-only too (#2233)
        $this->middleware(['auth', 'can:admin']);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.groups.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->defaultSortCriteria = ['groups.name', 'asc'];


        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_group',
            'internal_group_index',
            action([GroupsController::class, 'index']),
            $this->filter,
            Group::query()->select('groups.*'),
            ['groups.created_at' => 'desc'],
        );

        $groups = $listResultSet->getList()->paginate($listResultSet->getLimit());

        return view('groups.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
            ->with(compact('groups'))
            ->render();
    }

    /**
     * Display a listing of the resource.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    /**
     * Reset the limit, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_group', $request->get('key') ?? 'internal_group_index', filters: false);

        return redirect()->route('groups.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_group', $request->get('key') ?? 'internal_group_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'groups.index'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('groups.create-tw')
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GroupRequest $request, Group $group): RedirectResponse
    {
        $msg = '';

        $group = $group->create($this->validatedFields($request));

        $group->permissions()->attach($request->input('permission_list', []));
        $group->users()->attach($request->input('user_list', []));

        flash()->success('Success', 'Your group has been created');

        return redirect()->route('groups.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group): View
    {
        return view('groups.show-tw', compact('group'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Group $group): View
    {
        return view('groups.edit-tw', compact('group'))
          ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Group $group, GroupRequest $request): RedirectResponse
    {
        $msg = '';

        $group->fill($this->validatedFields($request))->save();

        $group->permissions()->sync($request->input('permission_list', []));

        $group->users()->sync($request->input('user_list', []));

        return redirect('groups');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return redirect('groups');
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['groups.name' => 'Name', 'groups.created_at' => 'Created At', 'groups.label' => 'Label', 'groups.level' => 'Level'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'permissionOptions' => Permission::orderBy('name')->pluck('name', 'id')->all(),
            'userOptions' => ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * The validated fields, with a blank description stored as '' — the column is
     * NOT NULL without a default, so a null description was a 500.
     *
     * @return array<string, mixed>
     */
    protected function validatedFields(GroupRequest $request): array
    {
        $fields = $request->validated();
        $fields['description'] = $fields['description'] ?? '';

        return $fields;
    }
}
