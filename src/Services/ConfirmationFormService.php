<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Models\ModelInterface;

final class ConfirmationFormService
{
    public function __construct(private FormService $formService)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<mixed> $errors
     */
    public function render(
        ModelInterface $model,
        ConfirmationFormConfig $config,
        array $data,
        string $csrfToken,
        array $errors = []
    ): string {
        $fields = $model->getFields($config->context, $data);
        $formHtml = $this->formService->formHtml(
            $config->action,
            $fields,
            $csrfToken,
            [
                'description' => $config->description,
                'buttonClass' => $config->buttonClass,
                'buttonID' => $config->buttonId,
                'buttonText' => $config->buttonText,
                'data' => $data,
            ]
        );

        return $this->formService->addMessages(
            $formHtml,
            $errors,
            [],
            $model
        );
    }
}
