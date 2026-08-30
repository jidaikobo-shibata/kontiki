<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Jidaikobo\Kontiki\Services\ConfirmationFormConfig;
use Jidaikobo\Kontiki\Services\RecordMutationFeedbackConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait TrashRestoreTrait
{
    public function trashIndex(Request $request, Response $response): Response
    {
        return $this->index($request, $response, 'trash');
    }

    public function trash(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        return static::confirmTrashRestore($request, $response, $id, 'trash');
    }

    public function restore(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        return static::confirmTrashRestore($request, $response, $id, 'restore');
    }

    public function confirmTrashRestore(
        Request $request,
        Response $response,
        int $id,
        string $actionType
    ): Response {
        $data = $this->model->getById($id);

        if (!$data) {
            return $this->redirectResponse($request, $response, "{$this->label}_index");
        }

        $buttonText = $actionType == 'trash' ? 'to_trash' : $actionType;
        $buttonClass = $actionType == 'trash' ? 'btn-danger' : 'btn-success';

        $formHtml = $this->confirmationFormService->render(
            $this->model,
            new ConfirmationFormConfig(
                $actionType,
                "/{$this->adminDirName}/{$actionType}/{$id}",
                __(
                    "x_{$actionType}_confirm",
                    "Are you sure you want to {$actionType} this :name?",
                    ['name' => __($this->label)]
                ),
                $buttonClass,
                "main{$actionType}Btn",
                __($buttonText)
            ),
            $data,
            $this->csrfManager->getToken(),
            $this->flashManager->getData('errors', [])
        );

        return $this->renderResponse(
            $response,
            __(
                "x_{$actionType}",
                "{$actionType} :name",
                ['name' => __($this->label)]
            ),
            $formHtml
        );
    }

    public function handleTrash(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        return static::executeTrashRestore($request, $response, $id, 'trash');
    }

    public function handleRestore(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        return static::executeTrashRestore($request, $response, $id, 'restore');
    }

    public function executeTrashRestore(Request $request, Response $response, int $id, string $actionType): Response
    {
        $data = $request->getParsedBody() ?? [];

        // validate csrf token
        $redirectTo = "/{$this->adminDirName}/{$actionType}/{$id}";
        $redirectResponse = $this->validateCsrfToken($data, $request, $response, $redirectTo);
        if ($redirectResponse) {
            return $redirectResponse;
        }

        $result = $this->recordMutationService->changeState(
            $this->model,
            $id,
            $actionType
        );
        $redirectTo = $this->recordMutationFeedbackService->apply(
            $result,
            new RecordMutationFeedbackConfig(
                $this->label,
                "x_{$actionType}_success",
                ":name {$actionType} successfully.",
                "x_{$actionType}_failed",
                "Failed to {$actionType} :name",
                "/{$this->adminDirName}/index",
                "/{$this->adminDirName}/index"
            )
        );
        return $this->redirectResponse($request, $response, $redirectTo);
    }
}
