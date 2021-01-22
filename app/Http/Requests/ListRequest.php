<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ListRequestAdapter.
 *
 * This class is used to retrieve query parameters from the request used by the list
 */
class ListRequest
{
    const IS_EMPTY_FIELD_NAME = '_is_empty';

    private $requestStack;

    private $request;

    public function __construct(Request $request)
    {
        // copied over from cadence, but removing
        //$this->requestStack = $requestStack;

        $this->request = $request;
    }

    public function getSortFieldName(): ?string
    {
        $sort = $this->request->get('sort');

        return $sort ? $sort : null;
    }

    public function getFilters(): ?array
    {
        $filters = $this->request->get('filters');

        return $filters ? $filters : null;
    }

    public function getIsEmptyFilter(): bool
    {
        $filters = $this->request->get('filters');
        if (!is_null($filters) && array_key_exists(self::IS_EMPTY_FIELD_NAME, $filters)) {
            return 'true' === $filters[self::IS_EMPTY_FIELD_NAME];
        }

        return false;
    }

    public function getSortDirection(): ?string
    {
        $direction = $this->request->get('direction');

        return  $direction ? $direction : null;
    }

    public function getLimit(): ?int
    {
        // changed name from limit to rpp
        $limit = $this->request->query->getInt('limit');

        return $limit ? $limit : null;
    }

    public function getPage(): ?int
    {
        $page = $this->request->query->getInt('page');

        return $page ? $page : null;
    }

    public function getRoute(): ?string
    {
        return $this->request->attributes->get('_route') ?? null;
    }

    public function getRouteParams(): ?array
    {
        return $this->request->attributes->get('_route_params') ?? null;
    }
}
