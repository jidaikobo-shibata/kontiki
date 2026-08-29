<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

final class SaveRedirectService
{
    /** @param array<string, mixed> $data */
    public function previewTarget(array $data, string $adminDirectory): ?string
    {
        return ($data['preview'] ?? null) === '1'
            ? "/{$adminDirectory}/preview"
            : null;
    }

    public function formTarget(
        string $context,
        string $adminDirectory,
        ?int $id = null
    ): string {
        return $context === 'create'
            ? "/{$adminDirectory}/create"
            : "/{$adminDirectory}/edit/{$id}";
    }

    public function savedTarget(string $adminDirectory, int $id): string
    {
        return "/{$adminDirectory}/edit/{$id}";
    }

    public function indexTarget(string $adminDirectory): string
    {
        return "/{$adminDirectory}/index";
    }
}
