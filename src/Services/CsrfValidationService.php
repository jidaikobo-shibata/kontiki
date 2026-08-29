<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;

final class CsrfValidationService
{
    public function __construct(
        private CsrfManager $csrfManager,
        private FlashManager $flashManager
    ) {
    }

    /** @param array<string, mixed>|null $data */
    public function validate(?array $data): bool
    {
        $token = $data['_csrf_value'] ?? null;
        if (!is_string($token) || $token === '' || !$this->csrfManager->isValid($token)) {
            $this->flashManager->addErrors([
                ['messages' => [__('csrf_invalid', 'Invalid CSRF token.')]],
            ]);

            return false;
        }

        $this->csrfManager->regenerate();

        return true;
    }
}
