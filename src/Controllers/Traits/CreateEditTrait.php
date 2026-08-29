<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait CreateEditTrait
{
    private array $pendingMetaData;

    public function handleRenderCreateForm(
        Request $request,
        Response $response
    ): Response {
        return $this->renderCreateForm($request, $response);
    }

    public function handleRenderEditForm(
        Request $request,
        Response $response,
        array $args
    ): Response {
        return $this->renderEditForm($request, $response, $args);
    }

    public function renderCreateForm(
        Request $request,
        Response $response
    ): Response {
        $data = $this->model->getDataForForm('create', $this->flashManager);
        $fields = $this->model->getFields('create', $data);

        $formVars = [
            'buttonID' => 'mainSubmitBtn',
            'buttonText' => __("x_save", 'Save :name', ['name' => __($this->label)]),
            'data' => $data
        ];

        $formHtml = $this->formService->formHtml(
            "/{$this->adminDirName}/create",
            $fields,
            $this->csrfManager->getToken(),
            $formVars
        );
        $formHtml = $this->formService->addMessages(
            $formHtml,
            $this->flashManager->getData('errors', [])
        );

        return $this->renderResponse(
            $response,
            __("x_create", 'Create :name', ['name' => __($this->label)]),
            $formHtml
        );
    }

    public function renderEditForm(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = $args['id'];
        $data = $this->model->getDataForForm('edit', $this->flashManager, $id);

        if (!$data) {
            return $this->redirectResponse(
                $request,
                $response,
                "/{$this->adminDirName}/index"
            );
        }

        $fields = $this->model->getFields('edit', $data);

        $formVars = [
            'buttonID' => 'mainSubmitBtn',
            'buttonText' => __("x_save", 'Save :name', ['name' => __($this->label)]),
            'data' => $data
        ];

        $formHtml = $this->formService->formHtml(
            "/{$this->adminDirName}/edit/{$id}",
            $fields,
            $this->csrfManager->getToken(),
            $formVars,
        );
        $formHtml = $this->formService->addMessages(
            $formHtml,
            $this->flashManager->getData('errors', []),
            $this->flashManager->getData('success', [])
        );

        return $this->renderResponse(
            $response,
            __("x_edit", 'Edit :name', ['name' => __($this->label)]),
            $formHtml
        );
    }

    public function handleCreate(
        Request $request,
        Response $response
    ): Response {
        return $this->handleSave($request, $response, 'create');
    }

    public function handleEdit(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = $args['id'];
        return $this->handleSave($request, $response, 'edit', $id);
    }

    private function getDefaultRedirect(string $context, ?int $id = null): string
    {
        return $context === 'create'
            ? "/{$this->adminDirName}/create"
            : "/{$this->adminDirName}/edit/{$id}";
    }

    private function handleSave(
        Request $request,
        Response $response,
        string $context,
        ?int $id = null
    ): Response {
        $data = $request->getParsedBody() ?? [];
        $this->flashManager->setData('data', $data);

        // redirect preview
        if (isset($data['preview']) && $data['preview'] === '1') {
            return $this->redirectResponse(
                $request,
                $response,
                "/{$this->adminDirName}/preview"
            );
        }

        $defaultRedirect = $this->getDefaultRedirect($context, $id);

        // validate csrf token
        $errorResponse = $this->validateCsrfToken($data, $request, $response, $defaultRedirect);
        if ($errorResponse) {
            return $errorResponse;
        }

        // Validate post data
        if (!$this->isValidData($data, $context, $id)) {
            return $this->redirectResponse($request, $response, $defaultRedirect);
        }

        return $this->processAndRedirect($request, $response, $context, $id, $data);
    }

    /**
     * Validate input data against the field definitions.
     */
    private function isValidData(array $data, string $context, ?int $id): bool
    {
        $validationResult = $this->model->validate(
            $data,
            ['id' => $id, 'context' => $context]
        );

        if (!$validationResult['valid']) {
            $this->flashManager->addErrors($validationResult['errors']);
            return false;
        }

        return true;
    }

    /**
     * Process the save operation and handle redirection.
     */
    private function processAndRedirect(
        Request $request,
        Response $response,
        string $context,
        ?int $id,
        array $data
    ): Response {
        try {
            $id = $this->saveData($context, $id, $data);

            // not so good...
            $backStringAfterSaveKey = $this->backStringAfterSaveKey ??
                'x_save_success_and_redirect';
            $backStringAfterSave = $this->backStringAfterSave ??
                ':name Saved successfully. [Go to Index](:url)';

            $this->flashManager->addMessage(
                'success',
                __(
                    $backStringAfterSaveKey,
                    $backStringAfterSave,
                    [
                        'name' => __($this->label),
                        'url' => env('BASEPATH') . "/{$this->adminDirName}/index"
                    ]
                )
            );
            return $this->redirectResponse(
                $request,
                $response,
                "/{$this->adminDirName}/edit/{$id}"
            );
        } catch (\Exception $e) {
            $this->flashManager->addErrors([[$e->getMessage()]]);
            return $this->redirectResponse(
                $request,
                $response,
                $this->getDefaultRedirect($context, $id)
            );
        }
    }

    private function saveData(string $context, ?int $id, array $data): int
    {
        $data = $this->divideMetaData($data);

        if ($context === 'create') {
            $newId = $this->model->create($data);
            if ($newId === null) {
                throw new \RuntimeException('Failed to create record. No ID returned.');
            }
            $this->saveMetaData($newId);
            return $newId;
        }

        if ($context === 'edit' && $id !== null) {
            $this->model->update($id, $data);
            $this->saveMetaData($id);
            return $id;
        }

        throw new \InvalidArgumentException('Invalid action type or missing ID.');
    }

    private function divideMetaData(array $data): array
    {
        $MetaData = [];
        foreach ($this->model->getMetaDataFieldDefinitions() as $key => $definition) {
            if (isset($data[$key])) {
                $MetaData[$key] = $data[$key];
                unset($data[$key]);
            }
        }

        if (!empty($MetaData)) {
            $this->pendingMetaData = $MetaData;
        }

        return $data;
    }

    private function saveMetaData(int $id): void
    {
        if (empty($this->pendingMetaData)) {
            return;
        }

        foreach ($this->pendingMetaData as $key => $value) {
            $existing = $this->model->getMetaData($id, $key);

            if ($value === '' || $value === null) {
                $this->model->deleteMetaData($id, $key);
            } elseif ($existing !== null) {
                $this->model->updateMetaData($id, $key, $value);
            } else {
                $this->model->createMetaData($id, $key, $value);
            }
        }
        $this->pendingMetaData = [];
    }
}
