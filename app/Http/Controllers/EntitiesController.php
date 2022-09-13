<?php

namespace App\Http\Controllers;

use App\Filters\EntityFilters;
use App\Http\Requests\EntityRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Follow;
use App\Models\Photo;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\EventPublished;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Jamband\Ripple\Ripple;

class EntitiesController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected bool $hasFilter;

    protected EntityFilters $filter;

    public function __construct(EntityFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'follow']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entities.';

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entities.created_at' => 'desc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;

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
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Entity::query()->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');

        // set the default filter to active
        $defaultFilter = ['entity_status' => 'Active'];

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilter)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // count the most common entities in the recent past
        $latestEntities = Entity::withCount(['events' => function (Builder $query) {
            $query->where('events.start_at', '>', Carbon::now()->subMonths(3));
        }])
        ->orderBy('events_count', 'desc')
        ->paginate(6);

        return view('entities.index')
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
            ->with(compact('entities', 'latestEntities'))
            ->render();
    }

    /**
     * Display a listing of the resource that the user is following.
     *
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $this->middleware('auth');

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        //$baseQuery = Entity::query()->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');

        $baseQuery = Entity::join('follows', 'entities.id', '=', 'follows.object_id')
        ->where('follows.object_type', '=', 'entity')
        ->where('follows.user_id', '=', $this->user->id)
        ->select('entities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
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
            ->with(compact('entities'))
            ->with(['type' => 'Following'])
            ->render();
    }

    protected function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Gets the base query.
     */
    public function getBaseQuery(): Builder
    {
        return Entity::query()->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');
    }

    /**
     * Display a listing of entities by type.
     *
     * @throws \Throwable
     */
    public function indexTypes(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $type
    ): string {
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_roles');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['entity_type' => $type]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
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
            ->with(['type' => $type])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of entities by role.
     *
     * @throws \Throwable
     */
    public function indexRoles(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        ?string $role
    ): string {
        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_roles');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['role' => $role]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
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
            ->with(['role' => $role])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Filter the list of entities.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only entities are returned
        $baseQuery = Entity::query()
                        ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
                        ->leftJoin('entity_statuses', 'entities.entity_status_id', '=', 'entity_statuses.id')
                        ->select('entities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
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
            ->with(compact('entities'))
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
        $keyPrefix = $request->get('key') ?? 'internal_entity_index';
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('entities.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @throws \Throwable
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_entity_index';
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('entities.index');
    }

    /**
     * Display a listing of entities by tag.
     *
     * @throws \Throwable
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug,
        StringHelper $stringHelper
    ): string {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_tags');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['tag' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
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
            ->with(['tag' => $tag])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of entities by alias.
     *
     * @throws \Throwable
     */
    public function indexAliases(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $alias
    ): string {
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_tags');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Entity::getByAlias($alias)->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
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
            ->with(['role' => $alias])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display an entity when passed the slug.
     *
     * @return Response|string
     *
     * @throws \Throwable
     */
    public function indexSlug(string $slug)
    {
        $entity = Entity::getBySlug(strtolower($slug))->firstOrFail();

        return view('entities.show')
            ->with(compact('entity'))
            ->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response|string
     */
    public function create()
    {
        return view('entities.create')->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EntityRequest $request, Entity $entity): Response
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);
        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        // check the elements in the alias list, and if any don't match, add the alias
        foreach ($aliasArray as $key => $alias) {
            if (DB::table('aliases')->where('id', $alias)->count() > 0) {
                $newAlias = new Alias();
                $newAlias->name = ucwords(strtolower($alias));
                $newAlias->save();

                $aliasSyncArray[] = $newAlias->id;

                $msg .= ' Added alias '.$alias.'.';
            } else {
                $aliasSyncArray[$key] = $alias;
            }
        }

        $entity = $entity->create($input);

        $entity->tags()->attach($syncArray);
        $entity->aliases()->attach($aliasSyncArray);
        $entity->roles()->attach($request->input('role_list', []));

        // add to activity log
        Activity::log($entity, $this->user, 1);

        flash()->success('Success', 'Your entity has been created');

        // return redirect()->route('entities.index');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity): View
    {
        app('redirect')->setIntendedUrl(url()->current());

        $threads = $entity->threads()->paginate($this->limit);

        $embeds = $this->getEmbedsForEntity($entity);

        // get all the tracks as streamable URLs
        // $tracks = $this->getTracksFromUrl('https://0h85.bandcamp.com/');

        return view('entities.show', compact('entity', 'threads', 'embeds', 'tracks'));
    }

    /**
     * Returns an array of embeds for an entity
     */
    protected function getEmbedsForEntity(Entity $entity): array
    {
        $embeds = [];

        // ripple extracts data from audio provider links
        $ripple = new Ripple;

        // LARGE - Set up a large bandcamp player
        // $ripple->options(['curl' => [], 'embed' => ['Bandcamp' => 'size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/'], 'response' => []]);

        // MEDIUM - Set up a medium bandcamp player
        $ripple->options([
            'curl' => [],
            'embed' => [
                'Bandcamp' => '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/artwork=small/transparent=true/',
                'Soundcloud' => '&color=%23ff5500&auto_play=true&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false',
 
            ],
            'response' => []
        ]);
       
        // SMALL - Set up a small bandcamp player
        //$ripple->options(['curl' => [], 'embed' => ['Bandcamp' => '/size=small/bgcol=333333/linkcol=0687f5/transparent=true/'], 'response' => []]);
        
        // get some data about the entities bandcamp links
        $links = $entity->links;

        // convert the entitie's links into embeds when they contain embeddable audio
        foreach ($links as $link) {
            // bandcamp
            if (strpos($link->url, "bandcamp.com") && (strpos($link->url, "album") || strpos($link->url, "track"))) {
                // it's a bandcamp link, so request info
                $ripple->request($link->url);
                $embeds[] = sprintf('<iframe style="border: 0; width: 100%%; height: 120px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            }
            // soundcloud
            if (strpos($link->url, "soundcloud.com") && substr_count($link->url, '/') > 3) {
                // it's a soundcloud link, so request info

                $ripple->request($link->url);
                $embeds[] = sprintf('<iframe style="border: 0; width: 100%%; height: 120px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            }
            // youtube - disabled for now as any copywritten videos won't work
            // if (strpos($link->url, "youtube.com")) {
            //     // it's a youtube link, so request info

            //     $ripple->request($link->url);
            //     var_dump($ripple->embed());
            //     $embeds[] = sprintf('<iframe style="border: 0; width: 300px; height: 300px;"  src="%s" allowfullscreen seamless></iframe>', $ripple->embed());
            // }
        }

        return $embeds;
    }

    
    /**
     * Returns an array of tracks for an entity - call this with ajax so it's not blocking
     */
    public function getTracksFromUrl($url): array
    {
        // now collect tracks from all root bandcamp links
        // $url = https://0h85.bandcamp.com/;
        $trackUrls = [];

        $httpClient = new \GuzzleHttp\Client();

        $response = $httpClient->get($url);
        $htmlString = (string) $response->getBody();

        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $albumLinks = $xpath->evaluate("//a[contains(@href,'album')]");

        // build a list of links
        $albumUrls = [];
        foreach ($albumLinks as $albumLink) {
            if (strpos($albumLink->getAttribute("href"), 'https') === 0) {
                if (!in_array($albumLink->getAttribute("href"), $albumUrls)) {
                    $albumUrls[] = $albumLink->getAttribute("href");
                }
            }
        }

        $trackLinks = $xpath->evaluate("//a[contains(@href,'track')]");

        // build a list of links
        $trackUrls = [];
        foreach ($trackLinks as $trackLink) {
            if (strpos($trackLink->getAttribute("href"), 'https') === 0) {
                if (!in_array($trackLink->getAttribute("href"), $trackUrls)) {
                    $trackUrls[] = $trackLink->getAttribute("href");
                }
            }
        }

        // spider the album urls to get the rest of the tracks

        // dump($albumUrls);
        foreach ($albumUrls as $albumUrl) {
            $parsedUrl = parse_url($albumUrl);
            $baseUrl = $parsedUrl["scheme"]."://".$parsedUrl["host"];

            $trackResponse = $httpClient->get($albumUrl);
            $htmlString = (string) $trackResponse->getBody();

            // may instead be able to use header meta data on album pages
            $doc = new DOMDocument();
            $doc->loadHTML($htmlString);
            $xpath = new DOMXPath($doc);
    
            $trackLinks = $xpath->evaluate("//a[contains(@href,'track')]");

            foreach ($trackLinks as $trackLink) {
                $trackFullUrl = $baseUrl.$trackLink->getAttribute("href");
                if (!strpos($trackLink->getAttribute("href"), '?')) {
                    if (!in_array($trackFullUrl, $trackUrls)) {
                        $trackUrls[] = $trackFullUrl;
                    }
                }
            }
        }

        // dump($trackUrls);

        $embedUrls = $this->getEmbedsFromTracks($trackUrls);

        return $embedUrls;
    }


    /**
     * Get the embed URLs by querying buymusic API
     */
    public function getEmbedsFromTracks(array $trackUrls): array
    {
        $baseUrl = "https://buymusic.club/api/bandcamp/";
        $embedUrls = [];

        $httpClient = new \GuzzleHttp\Client();

        foreach ($trackUrls as $trackUrl) {
            $url = sprintf("%s?url=%s", $baseUrl, $trackUrl);
            $response = $httpClient->get($url);
            $jsonString = (string) $response->getBody();
            $obj = json_decode($jsonString);
            $embedUrls[] = $obj->streamURL;
        }

        return $embedUrls;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity): View
    {
        $this->middleware('auth');

        return view('entities.edit', compact('entity'))
        ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Entity $entity, EntityRequest $request): RedirectResponse
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));

        $entity->fill($input)->save();

        // if we got this far, it worked
        $msg = 'Updated entity. ';

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);

        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        // check the elements in the alias list, and if any don't match, add the alias
        foreach ($aliasArray as $key => $alias) {
            if (!Alias::find($alias)) {
                $newAlias = new Alias();
                $newAlias->name = ucwords(strtolower($alias));
                $newAlias->save();

                $aliasSyncArray[strtolower($alias)] = $newAlias->id;

                $msg .= ' Added alias '.$alias.'.';
            } else {
                $aliasSyncArray[$key] = $alias;
            }
        }

        $entity->tags()->sync($syncArray);
        $entity->aliases()->attach($aliasSyncArray);
        $entity->roles()->sync($request->input('role_list', []));

        // add to activity log
        Activity::log($entity, $this->user, 2);

        // flash this message
        flash()->success('Success', $msg);

        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity): RedirectResponse
    {
        // add to activity log
        Activity::log($entity, $this->user, 3);

        $entity->delete();

        return redirect('entities');
    }

    /**
     * Add a photo to an entity.
     */
    public function addPhoto(int $id, Request $request): void
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = $request->file('file')->storePubliclyAs('photos', $fileName, 'external');

        // attach to entity
        if ($entity = Entity::find($id)) {
            $photo = $this->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (isset($entity->photos) && 0 === count($entity->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to entity
            $entity->addPhoto($photo);
        }
    }

    protected function makePhoto(UploadedFile $file): Photo
    {
        return Photo::named($file->getClientOriginalName())->makeThumbnail();
    }

    /**
     * Mark user as following the entity.
     *
     * @return Response|array
     *
     * @throws \Throwable
     */
    public function follow(int $id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        // check if the entity exists
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // check if the user already follows
        if ($entity->followedBy($this->user) !== null) {
            flash()->error('Error', 'You are already following '.$entity->name);

            return redirect()->route('entities.show', compact('entity'));
        }

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'entity'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $follow->save();

        Log::info('User '.$id.' is following '.$entity->name);

        // add to activity log
        Activity::log($entity, $this->user, 6);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the entity - '.$entity->name,
                'Success' => view('entities.single')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now following the entity - '.$entity->name);

        return redirect()->intended('/entities/'.$entity->slug);
    }

    /**
     * Mark user as unfollowing the entity.
     *
     * @return Response|array
     *
     * @throws \Throwable
     */
    public function unfollow(int $id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // delete the follow
        $follow = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'entity')->first();
        $follow->delete();

        // add to activity log
        Activity::log($entity, $this->user, 7);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the entity - '.$entity->name,
                'Success' => view('entities.single')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are no longer following the entity - '.$entity->name);

        return back();
    }

    /**
     * Tweet this event.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function tweet(int $id)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // Add a twitter notification
        $entity->notify(new EventPublished());

        Log::info('User '.$id.' tweeted '.$entity->name);

        flash()->success('Success', 'You tweeted the entity - '.$entity->name);

        return back();
    }

    /**
     * Get the default sort array.
     */
    protected function getDefaultSortCriteria(): array
    {
        return ['id' => 'desc'];
    }

    protected function unauthorized(EntityRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['entities.name' => 'Name', 'entity_types.name' => 'Entity Type', 'entities.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
            'roleOptions' => ['' => ''] + Role::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'entityTypeOptions' => ['' => ''] + EntityType::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'entityStatusOptions' => ['' => ''] +  EntityStatus::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'entityTypeOptions' => ['' => ''] + EntityType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityStatusOptions' => EntityStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'aliasOptions' => Alias::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'roleOptions' => Role::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userOptions' => ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }
}
