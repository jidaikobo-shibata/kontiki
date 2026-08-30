<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

final class RecordMutationResult
{
    public const FAILURE_EXCEPTION = 'exception';
    public const FAILURE_OPERATION = 'operation';
    public const FAILURE_VALIDATION = 'validation';

    /** @param array<mixed> $errors */
    private function __construct(
        public readonly bool $success,
        public readonly ?string $failure,
        public readonly array $errors = []
    ) {
    }

    public static function success(): self
    {
        return new self(true, null);
    }

    /** @param array<mixed> $errors */
    public static function failure(string $reason, array $errors = []): self
    {
        return new self(false, $reason, $errors);
    }
}
