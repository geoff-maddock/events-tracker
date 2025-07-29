<?php

namespace App\Http\Controllers\Api;

use App\Filters\OccurrenceWeekFilters;
use App\Http\Controllers\Controller;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\OccurrenceWeek;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccurrenceWeeksController extends Controller
{
    protected OccurrenceWeekFilters $filter;

    public function __construct(OccurrenceWeekFilters $filter)
    {
        $this->filter = $filter;
        parent::__construct();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_occurrence_week');
        $listParamSessionStore->setKeyPrefix('internal_occurrence_week_index');

        $baseQuery = OccurrenceWeek::query()->select('occurrence_weeks.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['occurrence_weeks.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();
        $query = $listResultSet->getList();
        $weeks = $query->paginate($listResultSet->getLimit());

        return response()->json($weeks);
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    public function show(OccurrenceWeek $occurrenceWeek): JsonResponse
    {
        return response()->json($occurrenceWeek);
    }
}
