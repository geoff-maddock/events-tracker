<?php

namespace App\Services\SessionStore;

/**
 * Interface ListParamStore.
 */
interface ListParameterStore
{
    /**
     * @return mixed
     */
    public function setFilters(?array $filters);

    /**
     * @return mixed
     */
    public function setSortFieldName($sort);

    /**
     * @return mixed
     */
    public function setSortDirection($sortDirection);

    /**
     * @return mixed
     */
    public function setLimit($limit);

    /**
     * @return mixed
     */
    public function setIsEmptyFilter(bool $isEmpty);

    /**
     * @return mixed
     */
    public function getFilters();

    /**
     * @return mixed
     */
    public function getSortFieldName();

    /**
     * @return mixed
     */
    public function getSortDirection();

    /**
     * @return mixed
     */
    public function getLimit();

    /**
     * @return mixed
     */
    public function getIsEmptyFilter();

    /**
     * @return mixed
     */
    public function save();
}
