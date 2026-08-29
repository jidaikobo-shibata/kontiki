<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Models\ModelInterface;

final class FormPageService
{
    public function __construct(private FormService $formService)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $formVars
     * @param array<mixed> $errors
     * @param array<mixed> $success
     */
    public function render(
        ModelInterface $model,
        string $context,
        string $action,
        array $data,
        string $csrfToken,
        array $formVars,
        array $errors,
        array $success = []
    ): string {
        $this->formService->setModel($model);
        $fields = $model->getFields($context, $data);
        $formHtml = $this->formService->formHtml(
            $action,
            $fields,
            $csrfToken,
            $formVars
        );

        return $this->formService->addMessages($formHtml, $errors, $success);
    }
}
