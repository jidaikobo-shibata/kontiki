<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait TrashRestoreTrait
{
    public function trashIndex(Request $request, Response $response): Response
    {
        $this->context = 'trash';
        return static::index($request, $response);
    }

    public function processFieldForTrashRestore(array $data): array
    {
        foreach ($data as &$field) {
            $field['attributes']['readonly'] = 'readonly';

            $existingClass = $field['attributes']['class'] ?? '';
            $field['attributes']['class'] = trim($existingClass . ' form-control-plaintext p-2');

            $field['description'] = '';
        }
        unset($field);

        return $data;
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

    public function confirmTrashRestore(Request $request, Response $response, int $id, string $actionType): Response
    {
        $data = $this->model->getById($id);

        if (!$data) {
            return $this->redirectResponse($request, $response, "{$this->postType}_index");
        }

        $data = $this->model->getFieldDefinitionsWithDefaults($data);
        $data = $this->processFieldForTrashRestore($data);

        $buttonText = $actionType == 'trash' ? 'to_trash' : $actionType;

        $formHtml = $this->formService->formHtml(
            "/admin/{$this->postType}/{$actionType}/{$id}",
            $data,
            $this->csrfManager->getToken(),
            __(
                "x_{$actionType}_confirm",
                "Are you sure you want to {$actionType} this :name?",
                ['name' => __($this->postType)]
            ),
            __($buttonText),
        );
        $formHtml = $this->formService->addMessages(
            $formHtml,
            $this->flashManager->getData('errors', [])
        );

        return $this->renderResponse(
            $response,
            __(
                "x_{$actionType}",
                "{$actionType} :name",
                ['name' => __($this->postType)]
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
        $redirectTo = "/admin/{$this->postType}/{$actionType}/{$id}";
        $redirectResponse = $this->validateCsrfToken($data, $request, $response, $redirectTo);
        if ($redirectResponse) {
            return $redirectResponse;
        }

        // execute data
        try {
            if ($this->model->$actionType($id)) {
                $this->flashManager->addMessage(
                    'success',
                    __(
                        "x_{$actionType}_success",
                        ":name {$actionType} successfully.",
                        ['name' => __($this->postType)]
                    )
                );
                return $this->redirectResponse($request, $response, "/admin/{$this->postType}/index");
            }
        } catch (\Exception $e) {
            $this->flashManager->addErrors([
                __("x_{$actionType}_failed", "Failed to {$actionType} :name", ['name' => __($this->postType)])
              ]);
        }

        $redirectTo = "/admin/{$this->postType}/index";
        return $this->redirectResponse($request, $response, $redirectTo);
    }
}
