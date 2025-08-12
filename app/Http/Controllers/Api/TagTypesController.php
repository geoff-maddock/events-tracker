<?php

namespace App\Http\Controllers\Api;

use App\Filters\TagTypeFilters;
use App\Http\Controllers\Controller;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\TagType;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagTypesController extends Controller
{
    protected TagTypeFilters $filter;

    public function __construct(TagTypeFilters $filter)
    {
        $this->filter = $filter;
        parent::__construct();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_tag_type');
        $listParamSessionStore->setKeyPrefix('internal_tag_type_index');

        $baseQuery = TagType::query()->select('tag_types.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['tag_types.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();
        $tagTypes = $query->paginate($listResultSet->getLimit());

        return response()->json($tagTypes);
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    public function show(TagType $tagType): JsonResponse
    {
        return response()->json($tagType);
    }
}

