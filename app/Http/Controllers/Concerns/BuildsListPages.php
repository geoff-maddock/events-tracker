<?php

namespace App\Http\Controllers\Concerns;

use App\Filters\QueryFilter;
use App\Http\Response\ResultSet\ListResultSet;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Database\Eloquent\Builder;

/**
 * The scaffold every filterable, sortable list page shares (#2406): point the
 * session store at the list, run the request through the list builder, then
 * save the session and hand the view its list controls.
 *
 * A list action is startList(), its own query tail (eager loads, paginate),
 * then finishList() for the view data.
 */
trait BuildsListPages
{
    /**
     * The select options for the filter form, as view data.
     *
     * @return array<string, mixed>
     */
    abstract protected function getFilterOptions(): array;

    /**
     * limitOptions, sortOptions and directionOptions, as view data.
     *
     * @return array<string, mixed>
     */
    abstract protected function getListControlOptions(): array;

    /**
     * Start a list page. The filtered, sorted query is $listResultSet->getList().
     *
     * @param string $baseIndex session key shared by a model's lists (e.g. "internal_series")
     * @param string $keyPrefix session key of this list's own filters and sort
     * @param string $indexUrl where the model's "index" tab points
     * @param array<string, string> $defaultSort
     * @param array<string, mixed> $defaultFilters
     * @param array<string, mixed> $parentFilter filters the page itself imposes (e.g. its tag)
     * @param null|array<int, string> $allowedSortFields when given, any other sort falls back to the default
     */
    protected function startList(
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $baseIndex,
        string $keyPrefix,
        string $indexUrl,
        QueryFilter $filter,
        Builder $baseQuery,
        array $defaultSort,
        array $defaultFilters = [],
        array $parentFilter = [],
        ?array $allowedSortFields = null,
        ?int $defaultLimit = null,
    ): ListResultSet {
        $this->useList($listParamSessionStore, $baseIndex, $keyPrefix);
        $listParamSessionStore->setIndexTab($indexUrl);

        $listEntityResultBuilder
            ->setFilter($filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilters)
            ->setParentFilter($parentFilter)
            ->setDefaultSort($defaultSort);
        if ($allowedSortFields !== null) {
            $listEntityResultBuilder->setAllowedSortFields($allowedSortFields);
        }
        if ($defaultLimit !== null) {
            $listEntityResultBuilder->setDefaultLimit($defaultLimit);
        }

        return $listEntityResultBuilder->listResultSetFactory();
    }

    /**
     * Save the list's session state and return the view data every list view
     * reads, plus $extra.
     *
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    protected function finishList(
        ListParameterSessionStore $listParamSessionStore,
        ListResultSet $listResultSet,
        bool $rememberSort = false,
        array $extra = [],
    ): array {
        if ($rememberSort) {
            // persist resolved sort values so subsequent requests (e.g. the filter form) don't revert to a different default
            $listParamSessionStore->setSortDirection($listResultSet->getSortDirection());
            $listParamSessionStore->setSortFieldName($listResultSet->getSort());
        }

        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return array_merge(
            [
                'limit' => $listResultSet->getLimit(),
                'sort' => $listResultSet->getSort(),
                'direction' => $listResultSet->getSortDirection(),
                'hasFilter' => $this->hasFilter,
                'filters' => $listResultSet->getFilters(),
            ],
            $this->getFilterOptions(),
            $this->getListControlOptions(),
            // last, so a caller's value always wins
            $extra
        );
    }

    /**
     * Forget a list's rows-per-page and sort, and with $filters its filters too.
     * The reset links name the list in ?key=.
     */
    protected function clearList(
        ListParameterSessionStore $listParamSessionStore,
        string $baseIndex,
        string $keyPrefix,
        bool $filters,
    ): void {
        $this->useList($listParamSessionStore, $baseIndex, $keyPrefix);

        if ($filters) {
            $listParamSessionStore->clearFilter();
        }
        $listParamSessionStore->clearSort();
    }

    private function useList(ListParameterSessionStore $listParamSessionStore, string $baseIndex, string $keyPrefix): void
    {
        $listParamSessionStore->setBaseIndex($baseIndex);
        $listParamSessionStore->setKeyPrefix($keyPrefix);
    }
}
