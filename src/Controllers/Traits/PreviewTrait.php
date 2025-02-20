<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PreviewTrait
{
    public function handlePreviewById(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $data = $this->prepareDataForRenderForm($this->model->getById($id));
        return static::renderPreview($response, $data);
    }

    public function handlePreview(Request $request, Response $response): Response
    {
        $data = $this->prepareDataForRenderForm();
        return static::renderPreview($response, $data);
    }

    protected function renderPreview(Response $response, array $data): Response
    {
        if (!isset($data['title']) || !isset($data['content'])) {
            return $this->renderResponse(
                $response,
                __('cannot_preview_title', 'Cannot Render Preview'),
                __('cannot_preview_desc', 'Preview cannot be reloaded. Please close the preview window and preview again.'),
                'preview/content.php'
            );
        } else {
            return $this->previewRenderer->render(
                $response,
                'preview.php',
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                ]);
        }
    }
}
