<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\BaseModel;
use Jidaikobo\Kontiki\Services\ModelValidationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ModelValidationServiceTest extends TestCase
{
    private FlashManager&MockObject $flashManager;
    private BaseModel&MockObject $model;
    private ModelValidationService $service;

    protected function setUp(): void
    {
        $this->flashManager = $this->createMock(FlashManager::class);
        $this->model = $this->getMockBuilder(BaseModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMock();
        $this->service = new ModelValidationService($this->flashManager);
    }

    public function testReturnsTrueWithoutRegisteringErrorsWhenValid(): void
    {
        $data = ['title' => 'Valid title'];
        $this->model->expects(self::once())
            ->method('validate')
            ->with($data, ['id' => 42, 'context' => 'edit'])
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->flashManager->expects(self::never())->method('addErrors');

        self::assertTrue($this->service->validate($this->model, $data, 'edit', 42));
    }

    public function testRegistersExistingErrorShapeWhenInvalid(): void
    {
        $data = ['title' => ''];
        $errors = ['title' => ['messages' => ['Title is required.']]];
        $this->model->expects(self::once())
            ->method('validate')
            ->with($data, ['id' => null, 'context' => 'create'])
            ->willReturn(['valid' => false, 'errors' => $errors]);
        $this->flashManager->expects(self::once())
            ->method('addErrors')
            ->with($errors);

        self::assertFalse($this->service->validate($this->model, $data, 'create'));
    }
}
