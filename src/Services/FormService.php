<?php

namespace Jidaikobo\Kontiki\Services;

use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Renderers\FormRenderer;
use Jidaikobo\Kontiki\Handlers\FormHandler;

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
    private ?ModelInterface $model = null;

    public function __construct(
        FormRenderer $formRenderer,
        FormHandler $formHandler,
        PhpRenderer $view
    ) {
        $this->formRenderer = $formRenderer;
        $this->formHandler = $formHandler;
        $this->view = $view;
    }

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }

    /**
     * Generate form HTML without additional processing.
     *
     * @param string $action    The form action URL.
     * @param array  $fields    The form fields definitions.
     * @param string $csrfToken CSRF Token.
     * @param array  $formVars  variables.
     *
     * @return string The generated HTML for the form.
     */
    public function formHtml(
        string $action,
        array $fields,
        string $csrfToken,
        array $formVars
    ): string {
        $this->formRenderer->setFields($fields);
        $this->view->addAttribute('formVars', $formVars);

        return $this->view->fetch(
            'forms/edit.php',
            [
                'actionAttribute' => env('BASEPATH', '') . $action,
                'csrfToken' => $csrfToken,
                'formHtml' => $this->formRenderer->render()
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
    public function addMessages(
        string $formHtml,
        array $errors,
        array $success = array()
    ): string {
        $this->formHandler->setHtml($formHtml);
        $this->formHandler->setModel($this->model);
        $this->formHandler->addErrors($errors);
        $this->formHandler->addSuccessMessages($success);
        return $this->formHandler->getHtml();
    }
}
