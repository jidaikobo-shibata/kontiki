<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Jidaikobo\Kontiki\Services\FormService;
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

        $fields = $this->model->getFields('delete', $data);

        $formVars = [
            'description' => __(
                "x_delete_confirm",
                "Are you sure you want to delete this :name?",
                ['name' => __($this->label)]
            ),
            'buttonClass' => 'btn-danger',
            'buttonID' => 'mainDeleteBtn',
            'buttonText' => __("delete", "Delete"),
            'data' => $data
        ];

        $formHtml = $this->formService->formHtml(
            "/{$this->adminDirName}/delete/{$id}",
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

        $results = $this->model->validate(
            $data,
            ['id' => $id, 'context' => 'delete']
        );

        if (!$results['valid']) {
            $this->flashManager->addErrors($results['errors']);
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

        // delete data
        try {
            if ($this->model->delete($id)) {
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
        } catch (\Exception $e) {
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
