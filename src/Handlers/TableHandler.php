<?php

namespace Jidaikobo\Kontiki\Handlers;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Utils\MessageUtils;

class TableHandler
{
    private ?ModelInterface $model = null;
    private string $tableHtml = '';

    public function setModel(ModelInterface $model): void
    {
        $this->model = $model;
    }
    public function setHtml(string $html): void
    {
        $this->tableHtml = $html;
    }

    public function addErrors(array $errors): void
    {
        if (empty($errors)) {
            return;
        }
        $this->tableHtml = MessageUtils::errorHtml($errors, $this->model) . $this->tableHtml;
    }

    public function addSuccessMessages(array $successMessages): void
    {
        if (empty($successMessages)) {
            return;
        }
        $successMessage = join($successMessages);
        $this->tableHtml = MessageUtils::alertHtml($successMessage) . $this->tableHtml;
    }

    public function getHtml(): string
    {
        return $this->tableHtml;
    }
}
