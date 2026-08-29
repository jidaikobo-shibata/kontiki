<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\BaseModel;

final class ModelValidationService
{
    public function __construct(private FlashManager $flashManager)
    {
    }

    /** @param array<string, mixed> $data */
    public function validate(
        BaseModel $model,
        array $data,
        string $context,
        ?int $id = null
    ): bool {
        $result = $model->validate(
            $data,
            ['id' => $id, 'context' => $context]
        );

        if ($result['valid']) {
            return true;
        }

        $this->flashManager->addErrors($result['errors']);

        return false;
    }
}
