<?php

namespace App\Http\Controllers\Api;

use App\Filters\VisibilityFilters;
use App\Http\Controllers\Controller;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Visibility;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisibilitiesController extends Controller
{
    protected VisibilityFilters $filter;

    public function __construct(VisibilityFilters $filter)
    {
        $this->filter = $filter;
        parent::__construct();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_visibility');
        $listParamSessionStore->setKeyPrefix('internal_visibility_index');

        $baseQuery = Visibility::query()->select('visibilities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['visibilities.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();
        $visibilities = $query->paginate($listResultSet->getLimit());

        return response()->json($visibilities);
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    public function show(Visibility $visibility): JsonResponse
    {
        return response()->json($visibility);
    }
}
