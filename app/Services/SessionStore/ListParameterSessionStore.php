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

    private Store $session;

    // this is the base index of the module
    private string $baseIndex = '';

    // this is the key prefix for the tab
    private string $keyPrefix = '';

    private ?array $filters;

    private ?bool $isEmptyFilter = false;

    private ?string $sortDirection;

    private ?int $limit;

    private ?string $sortFieldName;

    private ?string $indexTab;

    public function __construct(Store $session)
    {
        $this->session = $session;
    }

    public function setBaseIndex(string $baseIndex): ListParameterStore
    {
        $this->baseIndex = $baseIndex;

        return $this;
    }

    public function setKeyPrefix(string $keyPrefix): ListParameterStore
    {
        $this->keyPrefix = $keyPrefix;
        $this->loadSessionData();

        return $this;
    }

    public function setFilters(?array $filters): ListParameterStore
    {
        $this->filters = $filters;

        return $this;
    }

    public function setSortDirection(?string $sortDirection): ListParameterStore
    {
        $this->sortDirection = $sortDirection;

        return $this;
    }

    public function setLimit(?int $limit): ListParameterStore
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Sets the base index route for the tab.
     */
    public function setIndexTab(?string $indexTab): ListParameterStore
    {
        $this->indexTab = $indexTab;

        return $this;
    }

    public function setSortFieldName(?string $sort): ListParameterStore
    {
        $this->sortFieldName = $sort;

        return $this;
    }

    public function setIsEmptyFilter(?bool $isEmpty): ListParameterStore
    {
        $this->isEmptyFilter = $isEmpty;

        return $this;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function getIsEmptyFilter(): bool
    {
        return $this->isEmptyFilter;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getIndexTab(): ?string
    {
        return $this->indexTab;
    }

    public function getSortFieldName(): ?string
    {
        return $this->sortFieldName;
    }

    private function loadSessionData(): void
    {
        $this->indexTab = $this->session->get($this->baseIndex.self::SESSION_SUFFIX_INDEX_TAB);

        $this->filters = $this->session->get($this->keyPrefix.self::SESSION_SUFFIX_FILTERS);
        $this->sortDirection = $this->session->get($this->keyPrefix.self::SESSION_SUFFIX_SORT_DIRECTION);
        $this->limit = $this->session->get($this->keyPrefix.self::SESSION_SUFFIX_LIMIT);
        $this->indexTab = $this->session->get($this->keyPrefix.self::SESSION_SUFFIX_INDEX_TAB);
        $this->sortFieldName = $this->session->get($this->keyPrefix.self::SESSION_SUFFIX_SORT_FIELD);
        $this->isEmptyFilter = $this->session->get($this->keyPrefix.self::SESSION_SUFFIX_IS_EMPTY);
        if (is_null($this->isEmptyFilter)) {
            $this->isEmptyFilter = false;
        }
    }

    public function save(): void
    {
        // this stores the location of the current index tab
        $this->session->put($this->baseIndex.self::SESSION_SUFFIX_INDEX_TAB, $this->indexTab);

        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_FILTERS, $this->filters);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_SORT_DIRECTION, $this->sortDirection);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_LIMIT, $this->limit);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_SORT_FIELD, $this->sortFieldName);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_INDEX_TAB, $this->indexTab);

        // Only save the isEmpty boolean to the session if the value is true. Otherwise, null will be loaded as false.
        if ($this->isEmptyFilter) {
            $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_IS_EMPTY, $this->isEmptyFilter);
        } else {
            $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_IS_EMPTY, null);
        }

        $this->session->save();
    }

    public function clearFilter(): void
    {
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_FILTERS, null);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_IS_EMPTY, null);
        $this->session->save();
    }

    public function clearSort(): void
    {
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_SORT_DIRECTION, null);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_SORT_FIELD, null);
        $this->session->put($this->keyPrefix.self::SESSION_SUFFIX_LIMIT, null);
        $this->session->save();
    }
}
