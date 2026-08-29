<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Illuminate\Database\Query\Builder;
use Jidaikobo\Kontiki\Utils\Pagination;

trait IndexTrait
{
    protected Pagination $pagination;

    public function getIndexData(
        string $context = '',
        array $queryParams = []
    ): array {
        $query = $this->applyFiltersToQuery($context, $queryParams);

        // Set up pagination
        $totalItems = $query->count();
        $paged = (int)($queryParams['paged'] ?? 1);
        $perPage = (int)($queryParams['perPage'] ?? 10);
        $this->pagination = new Pagination($paged, $perPage);
        $this->pagination->setTotalItems($totalItems);

        // Fetch and process data
        $data = $query->limit($this->pagination->getLimit())
                      ->offset($this->pagination->getOffset())
                      ->get()
                      ->map(fn($item) => (array) $item)
                      ->toArray();

        // process data (ex: UTC to JST)
        $processedData = array_map(fn($item) => $this->processDataBeforeGet($item), $data);

        return $processedData;
    }

    /**
     * Applies all filters and conditions to the query builder.
     *
     * @param string $context context text.
     * @param array $queryParams The query parameters.
     * @return Builder The modified query.
     */
    private function applyFiltersToQuery(
        string $context = '',
        array $queryParams = []
    ): Builder {
        $query = $this->getQuery();
        $query = $this->buildSearchConditions($query, $queryParams['s'] ?? '');
        $query = $this->getAdditionalConditions($query, $context);
        $query = $this->applySorting($query, $queryParams);
        $query = $this->applyPostTypeFilter($query);
        return $query;
    }

    /**
     * Applies sorting conditions to the query.
     *
     * @param Builder $query The query builder instance.
     * @param array $queryParams The query parameters containing sorting details.
     * @return Builder The modified query.
     */
    private function applySorting(Builder $query, array $queryParams): Builder
    {
        if (!empty($queryParams['orderby']) && !empty($queryParams['order'])) {
            $validColumns = array_keys($this->getFields());
            $column = in_array($queryParams['orderby'], $validColumns, true) ?
                $queryParams['orderby'] :
                'id';
            $direction = strtoupper($queryParams['order']) === 'DESC' ? 'DESC' : 'ASC';
            return $query->orderBy($column, $direction);
        }

        return $query->orderBy('id', 'DESC');
    }

    /**
     * Applies the post_type filter to the query.
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query.
     */
    private function applyPostTypeFilter(Builder $query): Builder
    {
        if (!empty($this->getPostType())) {
            return $query->where('post_type', '=', $this->getPostType());
        }
        return $query;
    }

    public function getPagination(): Pagination
    {
        return $this->pagination;
    }
}
