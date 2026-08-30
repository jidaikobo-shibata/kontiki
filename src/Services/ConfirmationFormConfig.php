<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

final class ConfirmationFormConfig
{
    public function __construct(
        public readonly string $context,
        public readonly string $action,
        public readonly string $description,
        public readonly string $buttonClass,
        public readonly string $buttonId,
        public readonly string $buttonText
    ) {
    }
}
