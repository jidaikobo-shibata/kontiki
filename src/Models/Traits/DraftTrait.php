<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Illuminate\Database\Query\Builder;

trait DraftTrait
{
    private function applyDraftConditions(Builder $query): Builder
    {
        return $query->where('status', '=', 'draft');
    }

    private function applyNotDraftConditions(Builder $query): Builder
    {
        return $query->where('status', '=', 'published');
    }
}
