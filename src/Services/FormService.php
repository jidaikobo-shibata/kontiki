<?php

namespace Jidaikobo\Kontiki\Services;

use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Renderers\FormRenderer;
use Jidaikobo\Kontiki\Handlers\FormHandler;
use LogicException;

/**
 * FormService
 *
 * A service class to handle form rendering and processing.
 */
class FormService
{
    private PhpRenderer $view;
    private FormRenderer $formRenderer;
    private FormHandler $formHandler;
    private AdminUrlGenerator $adminUrlGenerator;
    private ?ModelInterface $model = null;

    public function __construct(
        FormRenderer $formRenderer,
        FormHandler $formHandler,
        PhpRenderer $view,
        ?AdminUrlGenerator $adminUrlGenerator = null
    ) {
        $this->formRenderer = $formRenderer;
        $this->formHandler = $formHandler;
        $this->view = $view;
        $this->adminUrlGenerator = $adminUrlGenerator
            ?? new AdminUrlGenerator(env('BASEPATH', ''));
    }

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }

    /**
     * Generate form HTML without additional processing.
     *
     * @param string $action    The form action URL.
     * @param array<string, array<string, mixed>> $fields Form field definitions.
     * @param string $csrfToken CSRF Token.
     * @param array<string, mixed> $formVars Template variables.
     *
     * @return string The generated HTML for the form.
     */
    public function formHtml(
        string $action,
        array $fields,
        string $csrfToken,
        array $formVars
    ): string {
        $this->view->addAttribute('formVars', $formVars);

        return $this->view->fetch(
            'forms/edit.php',
            [
                'actionAttribute' => $this->adminUrlGenerator->path($action),
                'csrfToken' => $csrfToken,
                'formHtml' => $this->formRenderer->renderFields($fields)
            ]
        );
    }

    /**
     * Process form HTML by adding errors and success messages.
     *
     * @param string $formHtml The raw form HTML to process.
     *
     * @return string The processed HTML with errors and success messages.
     */
    /**
     * @param array<mixed> $errors
     * @param array<mixed> $success
     */
    public function addMessages(
        string $formHtml,
        array $errors,
        array $success = array(),
        ?ModelInterface $model = null
    ): string {
        return $this->formHandler->decorate(
            $formHtml,
            $this->requireModel($model),
            $errors,
            $success
        );
    }

    private function requireModel(?ModelInterface $model): ModelInterface
    {
        $model ??= $this->model;
        if ($model === null) {
            throw new LogicException('A model is required to decorate a form.');
        }

        return $model;
    }
}
