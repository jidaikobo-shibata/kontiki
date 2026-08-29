<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Managers\FlashManager;
use Throwable;

final class SaveMessageService
{
    public function __construct(private FlashManager $flashManager)
    {
    }

    public function addSuccess(
        string $label,
        string $messageKey,
        string $messageTemplate,
        string $indexUrl
    ): void {
        $this->flashManager->addMessage(
            'success',
            __(
                $messageKey,
                $messageTemplate,
                ['name' => __($label), 'url' => $indexUrl]
            )
        );
    }

    public function addFailure(Throwable $exception): void
    {
        $this->flashManager->addErrors([[$exception->getMessage()]]);
    }
}
