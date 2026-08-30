<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

final class FileLifecycleResult
{
    public const FAILURE_DATABASE = 'database';
    public const FAILURE_NOT_FOUND = 'not_found';
    public const FAILURE_STORAGE = 'storage';
    public const FAILURE_UPLOAD = 'upload';
    public const FAILURE_VALIDATION = 'validation';

    /**
     * @param array<string, mixed> $data
     * @param array<mixed> $errors
     */
    private function __construct(
        public readonly bool $success,
        public readonly ?string $failure,
        public readonly array $data,
        public readonly array $errors
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function success(array $data = []): self
    {
        return new self(true, null, $data, []);
    }

    /** @param array<mixed> $errors */
    public static function failure(string $reason, array $errors = []): self
    {
        return new self(false, $reason, [], $errors);
    }
}
