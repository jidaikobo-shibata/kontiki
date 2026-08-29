<?php

namespace Jidaikobo\Kontiki\Models\Traits;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

trait SoftDeleteTrait
{
    protected string $softDeleteField = 'deleted_at';

    public function applySoftDeletedConditions(Builder $query): Builder
    {
        return $query->whereNotNull($this->softDeleteField);
    }

    public function applyNotSoftDeletedConditions(Builder $query): Builder
    {
        return $query->whereNull($this->softDeleteField);
    }

    public function trash($id): bool
    {
        $currentTime = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $data = $this->getById($id);
        if (!$data) {
            return false;
        }
        return $this->update($id, [$this->softDeleteField => $currentTime], true);
    }

    public function restore($id): bool
    {
        $data = $this->getById($id);
        if (!$data) {
            return false;
        }
        return $this->update($id, [$this->softDeleteField => null], true);
    }
}
