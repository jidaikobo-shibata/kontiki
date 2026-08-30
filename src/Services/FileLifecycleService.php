<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Models\FileModel;
use Throwable;

final class FileLifecycleService
{
    public function __construct(
        private FileService $fileService,
        private UploadPathMapper $pathMapper
    ) {
    }

    /** @param array<string, mixed> $uploadedFile */
    public function upload(
        FileModel $model,
        array $uploadedFile
    ): FileLifecycleResult {
        $uploadResult = $this->fileService->upload($uploadedFile);
        if (!$uploadResult['success']) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_UPLOAD,
                $uploadResult['errors']
            );
        }

        $uploadedPath = $uploadResult['path'];
        $fileUrl = $this->pathMapper->pathToUrl($uploadedPath);
        if ($fileUrl === '') {
            $this->fileService->removeUploadedFile($uploadedPath);
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_STORAGE
            );
        }

        $fileData = ['path' => $fileUrl];
        $validation = $model->validate($fileData, ['context' => 'create']);
        if (!$validation['valid']) {
            $this->fileService->removeUploadedFile($uploadedPath);
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_VALIDATION,
                $validation['errors']
            );
        }

        try {
            $insertId = $model->create($fileData);
            if (!$insertId) {
                $this->fileService->removeUploadedFile($uploadedPath);
                return FileLifecycleResult::failure(
                    FileLifecycleResult::FAILURE_DATABASE
                );
            }

            $data = $model->getById($insertId);
            if ($data === null) {
                $model->delete($insertId);
                $this->fileService->removeUploadedFile($uploadedPath);
                return FileLifecycleResult::failure(
                    FileLifecycleResult::FAILURE_DATABASE
                );
            }
        } catch (Throwable) {
            $this->fileService->removeUploadedFile($uploadedPath);
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_DATABASE
            );
        }

        return FileLifecycleResult::success($data);
    }

    public function delete(FileModel $model, int $fileId): FileLifecycleResult
    {
        $data = $model->getById($fileId);
        if ($data === null) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_NOT_FOUND
            );
        }

        $filePath = $this->pathMapper->urlToPath((string) ($data['path'] ?? ''));
        $stagedPath = $this->fileService->stageDeletion($filePath);
        if ($stagedPath === false) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_STORAGE
            );
        }

        try {
            $deleted = $model->delete($fileId);
        } catch (Throwable) {
            $deleted = false;
        }

        if (!$deleted) {
            $this->fileService->restoreDeletion($stagedPath, $filePath);
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_DATABASE
            );
        }

        if (!$this->fileService->finalizeDeletion($stagedPath)) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_STORAGE
            );
        }

        return FileLifecycleResult::success();
    }

    public function updateDescription(
        FileModel $model,
        int $fileId,
        mixed $description
    ): FileLifecycleResult {
        $data = $model->getById($fileId);
        if ($data === null) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_NOT_FOUND
            );
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

        $validation = $model->validate(
            $data,
            ['id' => $fileId, 'context' => 'edit']
        );
        if ($validation['valid'] !== true) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_VALIDATION,
                $validation['errors']
            );
        }

        try {
            $updated = $model->update($fileId, $data);
        } catch (Throwable) {
            $updated = false;
        }

        if (!$updated) {
            return FileLifecycleResult::failure(
                FileLifecycleResult::FAILURE_DATABASE,
                ['Failed to update item.']
            );
        }

        return FileLifecycleResult::success();
    }
}
