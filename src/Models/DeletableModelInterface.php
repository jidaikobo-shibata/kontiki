<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Models;

interface DeletableModelInterface extends ModelInterface
{
    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array{valid: bool, errors: array<mixed>}
     */
    public function validate(array $data, array $context): array;

    public function delete(int $id): bool;
}
