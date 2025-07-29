<?php

namespace App\Http\Controllers\Api;

use App\Filters\OccurrenceTypeFilters;
use App\Http\Controllers\Controller;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\OccurrenceType;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccurrenceTypesController extends Controller
{
    protected OccurrenceTypeFilters $filter;

    public function __construct(OccurrenceTypeFilters $filter)
    {
        $this->filter = $filter;
        parent::__construct();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_occurrence_type');
        $listParamSessionStore->setKeyPrefix('internal_occurrence_type_index');

        $baseQuery = OccurrenceType::query()->select('occurrence_types.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['occurrence_types.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();
        $types = $query->paginate($listResultSet->getLimit());

        return response()->json($types);
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    public function show(OccurrenceType $occurrenceType): JsonResponse
    {
        return response()->json($occurrenceType);
    }
}
