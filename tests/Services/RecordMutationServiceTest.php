<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use InvalidArgumentException;
use Jidaikobo\Kontiki\Models\DeletableModelInterface;
use Jidaikobo\Kontiki\Models\SoftDeletableModelInterface;
use Jidaikobo\Kontiki\Services\RecordMutationResult;
use Jidaikobo\Kontiki\Services\RecordMutationService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RecordMutationServiceTest extends TestCase
{
    private RecordMutationService $service;

    protected function setUp(): void
    {
        $this->service = new RecordMutationService();
    }

    public function testReturnsDeleteValidationErrors(): void
    {
        $errors = ['confirmation' => ['messages' => ['Required.']]];
        $model = $this->createMock(DeletableModelInterface::class);
        $model->expects(self::once())->method('validate')
            ->with(['confirm' => ''], ['id' => 42, 'context' => 'delete'])
            ->willReturn(['valid' => false, 'errors' => $errors]);

        $result = $this->service->validateDelete(
            $model,
            ['confirm' => ''],
            42
        );

        self::assertSame(RecordMutationResult::FAILURE_VALIDATION, $result->failure);
        self::assertSame($errors, $result->errors);
    }

    public function testDeletesRecord(): void
    {
        $model = $this->createMock(DeletableModelInterface::class);
        $model->expects(self::once())->method('delete')->with(42)
            ->willReturn(true);

        self::assertTrue($this->service->delete($model, 42)->success);
    }

    public function testDistinguishesFalseDeleteFromException(): void
    {
        $falseModel = $this->createMock(DeletableModelInterface::class);
        $falseModel->method('delete')->willReturn(false);
        $exceptionModel = $this->createMock(DeletableModelInterface::class);
        $exceptionModel->method('delete')->willThrowException(
            new RuntimeException('Database failed.')
        );

        self::assertSame(
            RecordMutationResult::FAILURE_OPERATION,
            $this->service->delete($falseModel, 42)->failure
        );
        self::assertSame(
            RecordMutationResult::FAILURE_EXCEPTION,
            $this->service->delete($exceptionModel, 42)->failure
        );
    }

    public function testUsesExplicitTrashAndRestoreOperations(): void
    {
        $model = $this->createMock(SoftDeletableModelInterface::class);
        $model->expects(self::once())->method('trash')->with(42)
            ->willReturn(true);
        $model->expects(self::once())->method('restore')->with(42)
            ->willReturn(true);

        self::assertTrue($this->service->changeState(
            $model,
            42,
            RecordMutationService::ACTION_TRASH
        )->success);
        self::assertTrue($this->service->changeState(
            $model,
            42,
            RecordMutationService::ACTION_RESTORE
        )->success);
    }

    public function testRejectsUnsupportedStateAction(): void
    {
        $model = $this->createMock(SoftDeletableModelInterface::class);
        $this->expectException(InvalidArgumentException::class);

        $this->service->changeState($model, 42, 'publish');
    }
}
