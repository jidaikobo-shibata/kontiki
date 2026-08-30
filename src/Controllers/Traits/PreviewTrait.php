<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PreviewTrait
{
    public function handlePreviewById(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = $args['id'];
        $data = $this->model->getById($id);
        return static::renderPreview($response, $data);
    }

    public function handlePreview(Request $request, Response $response): Response
    {
        $data = $this->model->getDataForForm('preview', $this->flashManager);
        return static::renderPreview($response, $data);
    }

    protected function setPreviewPath(): void
    {
        $this->previewRenderer = $this->previewRendererFactory->create(
            $this->adminDirName
        );
    }

    protected function renderPreview(Response $response, array $data): Response
    {
        $viewAttributes = $this->view->getAttributes();
        $lang = $viewAttributes['lang'] ?? env('APPLANG', 'en');
        $copyright = $viewAttributes['copyright'] ?? env('COPYRIGHT', '');

        if (!isset($data['title']) || !isset($data['content'])) {
            $pageTitle = __('cannot_preview_title');
            $content = $this->view->fetch(
                'error/cannot_preview.php',
                [
                    'pageTitle' => $pageTitle,
                    'content' => __('cannot_preview_desc'),
                ]
            );

            return $this->view->render(
                $response,
                'layout-error.php',
                [
                    'lang' => $lang,
                    'pageTitle' => $pageTitle,
                    'content' => $content
                ]
            );
        } else {
            static::setPreviewPath();
            return $this->previewRenderer->render(
                $response,
                'preview.php',
                [
                    'lang' => $lang,
                    'copyright' => $copyright,
                    'data' => $data,
                ]
            );
        }
    }
}
