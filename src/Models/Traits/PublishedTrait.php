<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

trait PublishedTrait
{
    protected string $publishedField = 'published_at';

    /**
     * Apply conditions to retrieve published posts.
     *
     * Conditions:
     * - Includes posts where `published_at` is `NULL` (considered as already published).
     * - Includes posts where `published_at` is in the past or equal to the current time.
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query with applied conditions.
     */
    public function applyPublisedConditions(Builder $query): Builder
    {
        $currentTime = Carbon::now('UTC')->format('Y-m-d H:i:s');
        return $query->where(function ($q) use ($currentTime) {
            $q->whereNull($this->publishedField) // Consider NULL as published
              ->orWhere($this->publishedField, '<=', $currentTime);
        });
    }

    /**
     * Apply conditions to retrieve scheduled (future) posts.
     *
     * Conditions:
     * - Excludes posts where `published_at` is `NULL` (not scheduled).
     * - Includes posts where `published_at` is in the future.
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query with applied conditions.
     */
    public function applyNotPublisedConditions(Builder $query): Builder
    {
        $currentTime = Carbon::now('UTC')->format('Y-m-d H:i:s');
        return $query->whereNotNull($this->publishedField) // Exclude NULL (not scheduled)
                     ->where($this->publishedField, '>', $currentTime);
    }
}
