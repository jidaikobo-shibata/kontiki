<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Jidaikobo\Kontiki\Handlers\TableHandler;
use Jidaikobo\Kontiki\Renderers\TableRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait IndexTrait
{
    private function index(
        Request $request,
        Response $response,
        string $context
    ): Response {
        // Get data using the new getData method
        $data = $this->model->getIndexData($context, $request->getQueryParams());

        // render table
        $content = $this->tableService->tableHtml(
            $data,
            $this->adminDirName,
            $this->getRoutes(),
            $context
        );

        // set messages
        $error = $this->flashManager->getData('errors', []);
        $success = $this->flashManager->getData('success', []);

        // render messages
        $content = $this->tableService->addMessages(
            $content,
            $error,
            $success
        );

        // pagination
        $paginationSuffix = $context == 'all' ? '' : '/' . $context;
        $content .= $this->model->getPagination()->render(
            env('BASEPATH', '') . "/{$this->adminDirName}/index" . $paginationSuffix
        );
        $totalItems = $this->model->getPagination()->getTotalItems();
        $currentPage = $this->model->getPagination()->getCurrentPage();

        $title = 'x_index_' . $context;
        $title_placeholder = $context . ' index of :name';

        $pageTitle = __(
            $title,
            $title_placeholder,
            ['name' => __($this->label)]
        );

        $pageNum = __(
            'x_page',
            ' index of :name',
            ['name' => $currentPage]
        );

        return $this->renderResponse(
            $response,
            $pageTitle,
            $content,
            'layout.php',
            [
                'title' => $pageTitle . ' (' . $pageNum . ')',
                'h1' => $pageTitle . ' (' . count($data) . '/'  . $totalItems . ')',
            ]
        );
    }
}
