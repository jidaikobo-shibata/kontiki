<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Models;

interface SoftDeletableModelInterface extends DeletableModelInterface
{
    public function trash(int $id): bool;

    public function restore(int $id): bool;
}
