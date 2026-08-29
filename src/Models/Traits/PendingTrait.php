<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Illuminate\Database\Query\Builder;

trait PendingTrait
{
    private function applyPendingConditions(Builder $query): Builder
    {
        return $query->where('status', '=', 'pending');
    }

    private function applyNotPendingConditions(Builder $query): Builder
    {
        return $query->where('status', '=', 'published');
    }
}
