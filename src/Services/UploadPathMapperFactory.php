<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

final class UploadPathMapperFactory
{
    public function __construct(
        private string $baseUrl,
        private string $uploadUrlDirectory,
        private string $projectPath,
        private string $uploadDirectory
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            env('BASEURL', ''),
            env('BASEURL_UPLOAD_DIR', ''),
            env('PROJECT_PATH', ''),
            env('UPLOADDIR', '')
        );
    }

    public function create(): UploadPathMapper
    {
        $baseUrl = rtrim($this->baseUrl, '/')
            . rtrim($this->uploadUrlDirectory, '/');
        $uploadDirectory = rtrim(
            $this->projectPath . $this->uploadDirectory,
            DIRECTORY_SEPARATOR
        );

        return new UploadPathMapper($baseUrl, $uploadDirectory);
    }
}
