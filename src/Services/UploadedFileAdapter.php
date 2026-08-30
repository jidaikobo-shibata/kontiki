<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class UploadedFileAdapter
{
    public function errorFromRequest(
        ServerRequestInterface $request,
        string $fieldName = 'attachment'
    ): ?int {
        $uploadedFile = $request->getUploadedFiles()[$fieldName] ?? null;

        return $uploadedFile instanceof UploadedFileInterface
            ? $uploadedFile->getError()
            : null;
    }

    /** @return array{name: string, tmp_name: string, size: int}|null */
    public function fromRequest(
        ServerRequestInterface $request,
        string $fieldName = 'attachment'
    ): ?array {
        $uploadedFile = $request->getUploadedFiles()[$fieldName] ?? null;
        if (!$uploadedFile instanceof UploadedFileInterface) {
            return null;
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        $name = $uploadedFile->getClientFilename();
        $size = $uploadedFile->getSize();
        $temporaryPath = $uploadedFile->getStream()->getMetadata('uri');
        if (
            !is_string($name)
            || $name === ''
            || !is_int($size)
            || $size < 0
            || !is_string($temporaryPath)
            || $temporaryPath === ''
        ) {
            return null;
        }

        return [
            'name' => $name,
            'tmp_name' => $temporaryPath,
            'size' => $size,
        ];
    }
}
