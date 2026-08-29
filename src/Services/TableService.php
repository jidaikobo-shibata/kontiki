<?php

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Renderers\TableRenderer;
use Jidaikobo\Kontiki\Handlers\TableHandler;
use Slim\Views\PhpRenderer;

class TableService
{
    private TableRenderer $tableRenderer;
    private TableHandler $tableHandler;
    private PhpRenderer $view;
    private ?ModelInterface $model = null;

    public function __construct(
        TableRenderer $tableRenderer,
        TableHandler $tableHandler,
        PhpRenderer $view
    ) {
        $this->tableRenderer = $tableRenderer;
        $this->tableHandler = $tableHandler;
        $this->view = $view;
    }

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }

    public function tableHtml(
        array $data,
        string $adminDirName,
        array $routes = [],
        string $context = 'all'
    ): string {
        $this->tableRenderer->setModel($this->model);
        return $this->tableRenderer->render(
            $data,
            $adminDirName,
            $routes,
            $context
        );
    }

    public function addMessages(
        string $tableHtml,
        array $errors,
        array $success = []
    ): string {
        $this->tableHandler->setModel($this->model);
        $this->tableHandler->setHtml($tableHtml);
        $this->tableHandler->addErrors($errors);
        $this->tableHandler->addSuccessMessages($success);
        return $this->tableHandler->getHtml();
    }
}
