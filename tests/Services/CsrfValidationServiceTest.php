<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CsrfValidationServiceTest extends TestCase
{
    private CsrfManager&MockObject $csrfManager;
    private FlashManager&MockObject $flashManager;
    private CsrfValidationService $service;

    protected function setUp(): void
    {
        $this->csrfManager = $this->createMock(CsrfManager::class);
        $this->flashManager = $this->createMock(FlashManager::class);
        $this->service = new CsrfValidationService(
            $this->csrfManager,
            $this->flashManager
        );
    }

    public function testAcceptsValidTokenAndRegeneratesIt(): void
    {
        $this->csrfManager->expects(self::once())
            ->method('isValid')
            ->with('valid-token')
            ->willReturn(true);
        $this->csrfManager->expects(self::once())->method('regenerate');
        $this->flashManager->expects(self::never())->method('addErrors');

        self::assertTrue(
            $this->service->validate(['_csrf_value' => 'valid-token'])
        );
    }

    /** @param array<string, mixed>|null $data */
    #[DataProvider('invalidDataProvider')]
    public function testRejectsMissingOrInvalidToken(?array $data): void
    {
        if (($data['_csrf_value'] ?? null) === 'invalid-token') {
            $this->csrfManager->expects(self::once())
                ->method('isValid')
                ->with('invalid-token')
                ->willReturn(false);
        } else {
            $this->csrfManager->expects(self::never())->method('isValid');
        }
        $this->csrfManager->expects(self::never())->method('regenerate');
        $this->flashManager->expects(self::once())
            ->method('addErrors')
            ->with([['messages' => ['Invalid CSRF token.']]]);

        self::assertFalse($this->service->validate($data));
    }

    /** @return iterable<string, array{array<string, mixed>|null}> */
    public static function invalidDataProvider(): iterable
    {
        yield 'null body' => [null];
        yield 'missing token' => [[]];
        yield 'empty token' => [['_csrf_value' => '']];
        yield 'non-string token' => [['_csrf_value' => 1]];
        yield 'invalid token' => [['_csrf_value' => 'invalid-token']];
    }
}
