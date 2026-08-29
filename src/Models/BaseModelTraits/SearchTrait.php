<?php

namespace Jidaikobo\Kontiki\Models\BaseModelTraits;

use Illuminate\Database\Query\Builder;

trait SearchTrait
{
    /**
     * Get searchable fields from the model's properties.
     *
     * @return array
     */
    protected function getSearchableFields(): array
    {
        $searchableColumns = [];
        foreach ($this->getFieldDefinitions() as $column => $config) {
            if (isset($config['searchable']) && $config['searchable'] === true) {
                $searchableColumns[] = $column;
            }
        }
        return $searchableColumns;
    }

    protected function getAdditionalConditions(Builder $query, string $context = 'all'): Builder
    {
        return $query;
    }

    public function buildSearchConditions(Builder $query, string $keyword = ''): Builder
    {
        if (!empty($keyword)) {
            $searchableColumns = $this->getSearchableFields();

            $query->where(function ($q) use ($keyword, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$keyword}%");
                }
            });
        }

        return $query;
    }
}
