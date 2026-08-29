<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Models;

interface PersistableModelInterface extends ModelInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data, bool $skipFieldFilter = false): ?int;

    /** @param array<string, mixed> $data */
    public function update(
        int $id,
        array $data,
        bool $skipFieldFilter = false
    ): bool;
}
