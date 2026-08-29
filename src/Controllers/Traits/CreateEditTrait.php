<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait CreateEditTrait
{
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
        $formVars = [
            'buttonID' => 'mainSubmitBtn',
            'buttonText' => __("x_save", 'Save :name', ['name' => __($this->label)]),
            'data' => $data
        ];

        $formHtml = $this->formPageService->render(
            $this->model,
            'create',
            "/{$this->adminDirName}/create",
            $data,
            $this->csrfManager->getToken(),
            $formVars,
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

        $formVars = [
            'buttonID' => 'mainSubmitBtn',
            'buttonText' => __("x_save", 'Save :name', ['name' => __($this->label)]),
            'data' => $data
        ];

        $formHtml = $this->formPageService->render(
            $this->model,
            'edit',
            "/{$this->adminDirName}/edit/{$id}",
            $data,
            $this->csrfManager->getToken(),
            $formVars,
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

    private function handleSave(
        Request $request,
        Response $response,
        string $context,
        ?int $id = null
    ): Response {
        $data = $request->getParsedBody() ?? [];
        $this->flashManager->setData('data', $data);

        // redirect preview
        $previewTarget = $this->saveRedirectService->previewTarget(
            $data,
            $this->adminDirName
        );
        if ($previewTarget !== null) {
            return $this->redirectResponse(
                $request,
                $response,
                $previewTarget
            );
        }

        $defaultRedirect = $this->saveRedirectService->formTarget(
            $context,
            $this->adminDirName,
            $id
        );

        // validate csrf token
        $errorResponse = $this->validateCsrfToken($data, $request, $response, $defaultRedirect);
        if ($errorResponse) {
            return $errorResponse;
        }

        // Validate post data
        $isValid = $this->modelValidationService->validate(
            $this->model,
            $data,
            $context,
            $id
        );
        if (!$isValid) {
            return $this->redirectResponse($request, $response, $defaultRedirect);
        }

        return $this->processAndRedirect($request, $response, $context, $id, $data);
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
            $id = $this->persistenceService->save(
                $this->model,
                $context,
                $id,
                $data
            );

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
                        'url' => env('BASEPATH')
                            . $this->saveRedirectService->indexTarget($this->adminDirName)
                    ]
                )
            );
            return $this->redirectResponse(
                $request,
                $response,
                $this->saveRedirectService->savedTarget($this->adminDirName, $id)
            );
        } catch (\Exception $e) {
            $this->flashManager->addErrors([[$e->getMessage()]]);
            return $this->redirectResponse(
                $request,
                $response,
                $this->saveRedirectService->formTarget(
                    $context,
                    $this->adminDirName,
                    $id
                )
            );
        }
    }
}
