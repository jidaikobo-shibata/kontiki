<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Jidaikobo\Kontiki\Services\ConfirmationFormConfig;
use Jidaikobo\Kontiki\Services\RecordMutationResult;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait DeleteTrait
{
    public function delete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = $args['id'];
        $data = $this->model->getById($id);

        if (!$data) {
            return $this->redirectResponse(
                $request,
                $response,
                "{$this->label}_index"
            );
        }

        $formHtml = $this->confirmationFormService->render(
            $this->model,
            new ConfirmationFormConfig(
                'delete',
                "/{$this->adminDirName}/delete/{$id}",
                __(
                    "x_delete_confirm",
                    "Are you sure you want to delete this :name?",
                    ['name' => __($this->label)]
                ),
                'btn-danger',
                'mainDeleteBtn',
                __("delete", "Delete")
            ),
            $data,
            $this->csrfManager->getToken(),
            $this->flashManager->getData('errors', [])
        );

        return $this->renderResponse(
            $response,
            __(
                "x_delete",
                "Delete :name",
                ['name' => __($this->label)]
            ),
            $formHtml
        );
    }

    public function handleDelete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = $args['id'];
        $data = $request->getParsedBody() ?? [];

        $validation = $this->recordMutationService->validateDelete(
            $this->model,
            $data,
            $id
        );

        if (!$validation->success) {
            $this->flashManager->addErrors($validation->errors);
            return  $this->redirectResponse(
                $request,
                $response,
                "/{$this->adminDirName}/index"
            );
        }

        // validate csrf token
        $redirectTo = "/{$this->adminDirName}/delete/{$id}";
        $redirectResponse = $this->validateCsrfToken($data, $request, $response, $redirectTo);
        if ($redirectResponse) {
            return $redirectResponse;
        }

        $result = $this->recordMutationService->delete($this->model, $id);
        if ($result->success) {
            $this->flashManager->addMessage(
                'success',
                __(
                    "x_delete_success",
                    ":name deleted successfully.",
                    ['name' => __($this->label)]
                )
            );
            return $this->redirectResponse(
                $request,
                $response,
                "/{$this->adminDirName}/index"
            );
        }
        if ($result->failure === RecordMutationResult::FAILURE_EXCEPTION) {
            $this->flashManager->addErrors([
                __(
                    "x_delete_failed",
                    "Failed to delete :name",
                    ['name' => __($this->label)]
                )
            ]);
        }

        $redirectTo = "/{$this->adminDirName}/edit/{$id}";
        return $this->redirectResponse($request, $response, $redirectTo);
    }
}
