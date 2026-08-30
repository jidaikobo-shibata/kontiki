<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

final class RecordMutationFeedbackConfig
{
    public function __construct(
        public readonly string $label,
        public readonly string $successMessageKey,
        public readonly string $successMessageTemplate,
        public readonly string $failureMessageKey,
        public readonly string $failureMessageTemplate,
        public readonly string $successTarget,
        public readonly string $failureTarget
    ) {
    }
}
