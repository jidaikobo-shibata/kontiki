<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait CreateEditTrait
{
    public function prepareDataForRenderForm(array $default = []): array
    {
        return $this->flashManager->getData('data', $default);
    }

    public function processDataForRenderForm(string $actionType, array $data): array
    {
        return $data;
    }

    public function renderCreateForm(Request $request, Response $response): Response
    {
        $data = $this->prepareDataForRenderForm();
        $data = $this->processDataForRenderForm('create', $data);

        $fields = $this->model->getFieldDefinitionsWithDefaults($data);
        $fields = $this->model->processFieldDefinitions('create', $fields);
        $postType = empty($this->postType) ? $this->model->getPsudoPostType() : $this->postType;

        $formHtml = $this->formService->formHtml(
            "/admin/{$postType}/create",
            $fields,
            $this->csrfManager->getToken(),
            '',
            __("x_save", 'Save :name', ['name' => __($postType)]),
        );
        $formHtml = $this->formService->addMessages(
            $formHtml,
            $this->flashManager->getData('errors', [])
        );

        return $this->renderResponse(
            $response,
            __("x_create", 'Create :name', ['name' => __($postType)]),
            $formHtml
        );
    }

    public function renderEditForm(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $data = $this->prepareDataForRenderForm($this->model->getById($id));
        $data = $this->processDataForRenderForm('edit', $data);

        if (!$data) {
            return $this->redirectResponse($request, $response, "/admin/{$this->postType}/index");
        }

        $fields = $this->model->getFieldDefinitionsWithDefaults($data);
        $fields = $this->model->processFieldDefinitions('edit', $fields);
        $postType = empty($this->postType) ? $this->model->getPsudoPostType() : $this->postType;

        $formHtml = $this->formService->formHtml(
            "/admin/{$postType}/edit/{$id}",
            $fields,
            $this->csrfManager->getToken(),
            '',
            __("x_save", 'Save :name', ['name' => __($postType)]),
        );
        $formHtml = $this->formService->addMessages(
            $formHtml,
            $this->flashManager->getData('errors', []),
            $this->flashManager->getData('success', [])
        );

        return $this->renderResponse(
            $response,
            __("x_edit", 'Edit :name', ['name' => __($postType)]),
            $formHtml
        );
    }

    public function handleCreate(Request $request, Response $response): Response
    {
        return $this->handleSave($request, $response, 'create');
    }

    public function handleEdit(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        return $this->handleSave($request, $response, 'edit', $id);
    }

    public function processDataForSave(string $actionType, array $data): array
    {
        return $data;
    }

    protected function getDefaultRedirect(string $actionType, ?int $id = null): string
    {
        return $actionType === 'create'
            ? "/admin/{$this->postType}/create"
            : "/admin/{$this->postType}/edit/{$id}";
    }

    protected function getFieldDefinitionsForAction(string $actionType, ?int $id = null): array
    {
        $fields = $actionType === 'create'
            ? $this->model->getFieldDefinitions()
            : $this->model->getFieldDefinitions(['id' => $id]);

        return $this->model->processFieldDefinitions($actionType, $fields);
    }

    protected function saveData(string $actionType, ?int $id, array $data): int
    {
        $data = $this->processDataForSave($actionType, $data);

        if ($actionType === 'create') {
            $newId = $this->model->create($data);
            if ($newId === null) {
                throw new \RuntimeException('Failed to create record. No ID returned.');
            }
            return $newId;
        }

        if ($actionType === 'edit' && $id !== null) {
            $this->model->update($id, $data);
            return $id;
        }

        throw new \InvalidArgumentException('Invalid action type or missing ID.');
    }

    protected function handleSave(Request $request, Response $response, string $actionType, ?int $id = null): Response
    {
        $data = $request->getParsedBody() ?? [];
        $this->flashManager->setData('data', $data);

        // redirect preview
        if (isset($data['preview']) && $data['preview'] === '1') {
            return $this->redirectResponse($request, $response, "/admin/{$this->postType}/preview");
        }

        $defaultRedirect = $this->getDefaultRedirect($actionType, $id);

        // validate csrf token
        $errorResponse = $this->validateCsrfToken($data, $request, $response, $defaultRedirect);
        if ($errorResponse) {
            return $errorResponse;
        }

        // Validate post data
        if (!$this->isValidData($data, $actionType, $id)) {
            return $this->redirectResponse($request, $response, $defaultRedirect);
        }

        return $this->processAndRedirect($request, $response, $actionType, $id, $data);
    }

    /**
     * Validate input data against the field definitions.
     */
    private function isValidData(array $data, string $actionType, ?int $id): bool
    {
        $fields = $this->getFieldDefinitionsForAction($actionType, $id);
        $validationResult = $this->model->validateByFields($data, $fields);

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
        string $actionType,
        ?int $id,
        array $data
    ): Response {
        try {
            $id = $this->saveData($actionType, $id, $data);
            $this->flashManager->addMessage(
                'success',
                __("x_save_success", ':name Saved successfully.', ['name' => __($this->postType)])
            );
            return $this->redirectResponse($request, $response, "/admin/{$this->postType}/edit/{$id}");
        } catch (\Exception $e) {
            $this->flashManager->addErrors([[$e->getMessage()]]);
            return $this->redirectResponse($request, $response, $this->getDefaultRedirect($actionType, $id));
        }
    }
}
