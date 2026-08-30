<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Models;

interface LegacyMetadataModelInterface
{
    /** @return array<string, mixed> */
    public function getAllMetaData(int $id): array;

    public function getMetaData(int $id, string $key): mixed;

    public function createMetaData(
        int $id,
        string $key,
        mixed $value
    ): void;

    public function updateMetaData(
        int $id,
        string $key,
        mixed $value
    ): void;

    public function deleteMetaData(int $id, string $key): void;
}
