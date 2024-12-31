<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Illuminate\Database\Query\Builder;

trait ExpiredTrait
{
    protected string $expiredField = 'expired_at';

    public function applyExpiredConditions(Builder $query): Builder
    {
        $currentTime = date('Y-m-d H:i:s');
        return $query->where($this->expiredField, '<=', $currentTime);
    }

    public function applyNotExpiredConditions(Builder $query): Builder
    {
        $currentTime = date('Y-m-d H:i:s');
        return $query->where(function ($q) use ($currentTime) {
            $q->whereNull($this->expiredField)
              ->orWhere($this->expiredField, '>', $currentTime);
        });
    }
}
