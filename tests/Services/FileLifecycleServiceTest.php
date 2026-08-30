<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Models\FileModel;
use Jidaikobo\Kontiki\Services\FileLifecycleResult;
use Jidaikobo\Kontiki\Services\FileLifecycleService;
use Jidaikobo\Kontiki\Services\FileService;
use Jidaikobo\Kontiki\Services\UploadPathMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FileLifecycleServiceTest extends TestCase
{
    private FileService&MockObject $fileService;
    private FileModel&MockObject $model;
    private FileLifecycleService $service;

    protected function setUp(): void
    {
        $this->fileService = $this->createMock(FileService::class);
        $this->model = $this->getMockBuilder(FileModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate', 'create', 'getById', 'update', 'delete'])
            ->getMock();
        $this->service = new FileLifecycleService(
            $this->fileService,
            new UploadPathMapper(
                'https://example.com/uploads',
                '/var/www/site/uploads'
            )
        );
    }

    public function testUploadReturnsSavedRecord(): void
    {
        $uploadedFile = ['name' => 'image.png'];
        $fileData = ['path' => 'https://example.com/uploads/2026/image.png'];
        $record = ['id' => 42] + $fileData;
        $this->expectSuccessfulPhysicalUpload($uploadedFile);
        $this->model->expects(self::once())->method('validate')
            ->with($fileData, ['context' => 'create'])
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->model->expects(self::once())->method('create')
            ->with($fileData)->willReturn(42);
        $this->model->expects(self::once())->method('getById')
            ->with(42)->willReturn($record);
        $this->fileService->expects(self::never())->method('removeUploadedFile');

        $result = $this->service->upload($this->model, $uploadedFile);

        self::assertTrue($result->success);
        self::assertSame($record, $result->data);
    }

    public function testUploadRemovesFileWhenValidationFails(): void
    {
        $uploadedFile = ['name' => 'image.png'];
        $errors = ['path' => ['messages' => ['Invalid path.']]];
        $this->expectSuccessfulPhysicalUpload($uploadedFile);
        $this->model->expects(self::once())->method('validate')
            ->willReturn(['valid' => false, 'errors' => $errors]);
        $this->model->expects(self::never())->method('create');
        $this->fileService->expects(self::once())->method('removeUploadedFile')
            ->with('/var/www/site/uploads/2026/image.png');

        $result = $this->service->upload($this->model, $uploadedFile);

        self::assertSame(FileLifecycleResult::FAILURE_VALIDATION, $result->failure);
        self::assertSame($errors, $result->errors);
    }

    public function testUploadRemovesFileWhenDatabaseThrows(): void
    {
        $uploadedFile = ['name' => 'image.png'];
        $this->expectSuccessfulPhysicalUpload($uploadedFile);
        $this->model->method('validate')
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->model->method('create')->willThrowException(
            new RuntimeException('Database failed.')
        );
        $this->fileService->expects(self::once())->method('removeUploadedFile')
            ->with('/var/www/site/uploads/2026/image.png');

        $result = $this->service->upload($this->model, $uploadedFile);

        self::assertSame(FileLifecycleResult::FAILURE_DATABASE, $result->failure);
    }

    public function testDeleteRestoresFileWhenDatabaseFails(): void
    {
        $this->model->expects(self::once())->method('getById')->with(42)
            ->willReturn([
                'id' => 42,
                'path' => 'https://example.com/uploads/2026/image.png',
            ]);
        $this->fileService->expects(self::once())->method('stageDeletion')
            ->with('/var/www/site/uploads/2026/image.png')
            ->willReturn('/var/www/site/uploads/2026/image.png.staged');
        $this->model->expects(self::once())->method('delete')->with(42)
            ->willReturn(false);
        $this->fileService->expects(self::once())->method('restoreDeletion')
            ->with(
                '/var/www/site/uploads/2026/image.png.staged',
                '/var/www/site/uploads/2026/image.png'
            );
        $this->fileService->expects(self::never())->method('finalizeDeletion');

        $result = $this->service->delete($this->model, 42);

        self::assertSame(FileLifecycleResult::FAILURE_DATABASE, $result->failure);
    }

    public function testDeleteFinalizesFileAfterDatabaseSucceeds(): void
    {
        $this->model->method('getById')->willReturn([
            'id' => 42,
            'path' => 'https://example.com/uploads/2026/image.png',
        ]);
        $this->fileService->method('stageDeletion')
            ->willReturn('/var/www/site/uploads/2026/image.png.staged');
        $this->model->expects(self::once())->method('delete')->with(42)
            ->willReturn(true);
        $this->fileService->expects(self::never())->method('restoreDeletion');
        $this->fileService->expects(self::once())->method('finalizeDeletion')
            ->with('/var/www/site/uploads/2026/image.png.staged')
            ->willReturn(true);

        $result = $this->service->delete($this->model, 42);

        self::assertTrue($result->success);
    }

    public function testUpdateDescriptionValidatesAndUpdatesExistingRecord(): void
    {
        $record = [
            'id' => 42,
            'path' => 'https://example.com/uploads/image.png',
            'description' => 'Old description',
        ];
        $updated = $record;
        $updated['description'] = 'New description';
        $this->model->expects(self::once())->method('getById')
            ->with(42)->willReturn($record);
        $this->model->expects(self::once())->method('validate')
            ->with($updated, ['id' => 42, 'context' => 'edit'])
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->model->expects(self::once())->method('update')
            ->with(42, $updated)->willReturn(true);

        $result = $this->service->updateDescription(
            $this->model,
            42,
            'New description'
        );

        self::assertTrue($result->success);
    }

    public function testUpdateDescriptionReturnsValidationErrors(): void
    {
        $errors = ['description' => ['messages' => ['Too short.']]];
        $this->model->method('getById')->willReturn([
            'id' => 42,
            'description' => 'Old description',
        ]);
        $this->model->method('validate')->willReturn([
            'valid' => false,
            'errors' => $errors,
        ]);
        $this->model->expects(self::never())->method('update');

        $result = $this->service->updateDescription(
            $this->model,
            42,
            'x'
        );

        self::assertSame(FileLifecycleResult::FAILURE_VALIDATION, $result->failure);
        self::assertSame($errors, $result->errors);
    }

    public function testUpdateDescriptionKeepsExistingValueWhenInputIsNull(): void
    {
        $record = ['id' => 42, 'description' => 'Existing description'];
        $this->model->method('getById')->willReturn($record);
        $this->model->expects(self::once())->method('validate')
            ->with($record, ['id' => 42, 'context' => 'edit'])
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->model->expects(self::once())->method('update')
            ->with(42, $record)->willReturn(true);

        $result = $this->service->updateDescription(
            $this->model,
            42,
            null
        );

        self::assertTrue($result->success);
    }

    public function testUpdateDescriptionReturnsNotFound(): void
    {
        $this->model->method('getById')->willReturn(null);
        $this->model->expects(self::never())->method('validate');
        $this->model->expects(self::never())->method('update');

        $result = $this->service->updateDescription(
            $this->model,
            42,
            null
        );

        self::assertSame(FileLifecycleResult::FAILURE_NOT_FOUND, $result->failure);
    }

    public function testUpdateDescriptionConvertsDatabaseExceptionToFailure(): void
    {
        $this->model->method('getById')->willReturn([
            'id' => 42,
            'description' => 'Old description',
        ]);
        $this->model->method('validate')->willReturn([
            'valid' => true,
            'errors' => [],
        ]);
        $this->model->method('update')->willThrowException(
            new RuntimeException('Database failed.')
        );

        $result = $this->service->updateDescription(
            $this->model,
            42,
            'New description'
        );

        self::assertSame(FileLifecycleResult::FAILURE_DATABASE, $result->failure);
        self::assertSame(['Failed to update item.'], $result->errors);
    }

    /** @param array<string, mixed> $uploadedFile */
    private function expectSuccessfulPhysicalUpload(array $uploadedFile): void
    {
        $this->fileService->expects(self::once())->method('upload')
            ->with($uploadedFile)
            ->willReturn([
                'success' => true,
                'path' => '/var/www/site/uploads/2026/image.png',
                'filename' => 'image.png',
                'errors' => [],
            ]);
    }
}
