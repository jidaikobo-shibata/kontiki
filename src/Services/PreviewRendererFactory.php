<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use InvalidArgumentException;
use Slim\Views\PhpRenderer;

final class PreviewRendererFactory
{
    public function __construct(private string $projectPath)
    {
        $this->projectPath = rtrim($projectPath, DIRECTORY_SEPARATOR);
    }

    public function create(string $adminDirectory): PhpRenderer
    {
        $adminDirectory = trim($adminDirectory, '/\\');
        if (
            $adminDirectory === ''
            || preg_match('#^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$#', $adminDirectory) !== 1
        ) {
            throw new InvalidArgumentException('Invalid preview admin directory.');
        }

        $relativeViewPath = 'views/' . $adminDirectory;
        $applicationPath = $this->projectPath . '/app/' . $relativeViewPath;
        $viewPath = is_dir($applicationPath)
            ? $applicationPath
            : $this->projectPath . '/src/' . $relativeViewPath;

        return new PhpRenderer($viewPath);
    }
}
