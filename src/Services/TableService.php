<?php

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Renderers\TableRenderer;
use Jidaikobo\Kontiki\Handlers\TableHandler;
use Slim\Views\PhpRenderer;
use LogicException;

class TableService
{
    private TableRenderer $tableRenderer;
    private TableHandler $tableHandler;
    protected PhpRenderer $view;
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

    /**
     * @param array<int, array<string, mixed>> $data
     * @param array<mixed> $routes
     */
    public function tableHtml(
        array $data,
        string $adminDirName,
        array $routes = [],
        string $context = 'all',
        ?ModelInterface $model = null
    ): string {
        return $this->tableRenderer->renderForModel(
            $this->requireModel($model),
            $data,
            $adminDirName,
            $routes,
            $context
        );
    }

    /**
     * @param array<mixed> $errors
     * @param array<mixed> $success
     */
    public function addMessages(
        string $tableHtml,
        array $errors,
        array $success = [],
        ?ModelInterface $model = null
    ): string {
        return $this->tableHandler->decorate(
            $tableHtml,
            $this->requireModel($model),
            $errors,
            $success
        );
    }

    private function requireModel(?ModelInterface $model): ModelInterface
    {
        $model ??= $this->model;
        if ($model === null) {
            throw new LogicException('A model is required to render a table.');
        }

        return $model;
    }
}
