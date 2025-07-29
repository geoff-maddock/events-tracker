<?php

namespace App\Http\Controllers\Api;

use App\Filters\OccurrenceDayFilters;
use App\Http\Controllers\Controller;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\OccurrenceDay;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccurrenceDaysController extends Controller
{
    protected OccurrenceDayFilters $filter;

    public function __construct(OccurrenceDayFilters $filter)
    {
        $this->filter = $filter;
        parent::__construct();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_occurrence_day');
        $listParamSessionStore->setKeyPrefix('internal_occurrence_day_index');

        $baseQuery = OccurrenceDay::query()->select('occurrence_days.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['occurrence_days.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();
        $days = $query->paginate($listResultSet->getLimit());

        return response()->json($days);
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    public function show(OccurrenceDay $occurrenceDay): JsonResponse
    {
        return response()->json($occurrenceDay);
    }
}
