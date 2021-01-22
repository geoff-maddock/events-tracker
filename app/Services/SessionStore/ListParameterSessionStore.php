<?php

namespace App\Services\SessionStore;

use Illuminate\Session\Store;

/**
 * Loads and stores list query parameters to the session.
 */
class ListParameterSessionStore implements ListParameterStore
{
    const SESSION_SUFFIX_FILTERS = '/index/filters';

    const SESSION_SUFFIX_LIMIT = '/index/limit';

    const SESSION_SUFFIX_SORT_FIELD = '/index/sort';

    const SESSION_SUFFIX_SORT_DIRECTION = '/index/direction';

    const SESSION_SUFFIX_IS_EMPTY = '/index/is-reset';

    const SESSION_SUFFIX_INDEX_TAB = '/index/tab';

    private $session;

    // this is the base index of the module
    private $baseIndex = '';

    // this is the key prefix for the tab
    private $keyPrefix = '';

    private ?array $filters;

    private $isEmptyFilter = false;

    private ?string $sortDirection;

    private ?int $limit;

    private ?string $sortFieldName;

    private ?string $indexTab;

    public function __construct(Store $session)
    {
        $this->session = $session;
    }

    public function setBaseIndex(string $baseIndex)
    {
        $this->baseIndex = $baseIndex;
    }

    public function setKeyPrefix(string $keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;
        $this->loadSessionData();
    }

    public function setFilters(?array $filters)
    {
        $this->filters = $filters;
    }

    public function setSortDirection($sortDirection)
    {
        $this->sortDirection = $sortDirection;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Sets the base index route for the tab.
     */
    public function setIndexTab($indexTab)
    {
        $this->indexTab = $indexTab;
    }

    public function setSortFieldName($sort)
    {
        $this->sortFieldName = $sort;
    }

    public function setIsEmptyFilter(bool $isEmpty)
    {
        $this->isEmptyFilter = $isEmpty;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getIsEmptyFilter(): bool
    {
        return $this->isEmptyFilter;
    }

    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getIndexTab()
    {
        return $this->indexTab;
    }

    public function getSortFieldName()
    {
        return $this->sortFieldName;
    }

    private function loadSessionData()
    {
        $this->indexTab = $this->session->get($this->baseIndex . self::SESSION_SUFFIX_INDEX_TAB);

        $this->filters = $this->session->get($this->keyPrefix . self::SESSION_SUFFIX_FILTERS);
        $this->sortDirection = $this->session->get($this->keyPrefix . self::SESSION_SUFFIX_SORT_DIRECTION);
        $this->limit = $this->session->get($this->keyPrefix . self::SESSION_SUFFIX_LIMIT);
        $this->indexTab = $this->session->get($this->keyPrefix . self::SESSION_SUFFIX_INDEX_TAB);
        $this->sortFieldName = $this->session->get($this->keyPrefix . self::SESSION_SUFFIX_SORT_FIELD);
        $this->isEmptyFilter = $this->session->get($this->keyPrefix . self::SESSION_SUFFIX_IS_EMPTY);
        if (is_null($this->isEmptyFilter)) {
            $this->isEmptyFilter = false;
        }
    }

    public function save()
    {
        // this stores the location of the current index tab
        $this->session->put($this->baseIndex . self::SESSION_SUFFIX_INDEX_TAB, $this->indexTab);

        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_FILTERS, $this->filters);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_SORT_DIRECTION, $this->sortDirection);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_LIMIT, $this->limit);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_SORT_FIELD, $this->sortFieldName);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_INDEX_TAB, $this->indexTab);

        // Only save the isEmpty boolean to the session if the value is true. Otherwise, null will be loaded as false.
        if ($this->isEmptyFilter) {
            $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_IS_EMPTY, $this->isEmptyFilter);
        } else {
            $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_IS_EMPTY, null);
        }

        $this->session->save();
    }

    public function clearFilter()
    {
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_FILTERS, null);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_IS_EMPTY, null);
        $this->session->save();
    }

    public function clearSort()
    {
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_SORT_DIRECTION, null);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_SORT_FIELD, null);
        $this->session->put($this->keyPrefix . self::SESSION_SUFFIX_LIMIT, null);
        $this->session->save();
    }
}
