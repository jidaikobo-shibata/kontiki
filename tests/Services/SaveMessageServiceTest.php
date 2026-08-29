<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Services\SaveMessageService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SaveMessageServiceTest extends TestCase
{
    public function testRegistersExistingSuccessMessageShape(): void
    {
        $flashManager = $this->createMock(FlashManager::class);
        $flashManager->expects(self::once())
            ->method('addMessage')
            ->with(
                'success',
                'Post Saved successfully. [Go to Index](/cms/post/index)'
            );

        (new SaveMessageService($flashManager))->addSuccess(
            'Post',
            'x_save_success_and_redirect',
            ':name Saved successfully. [Go to Index](:url)',
            '/cms/post/index'
        );
    }

    public function testRegistersExceptionMessageInExistingNestedShape(): void
    {
        $flashManager = $this->createMock(FlashManager::class);
        $flashManager->expects(self::once())
            ->method('addErrors')
            ->with([['Could not save.']]);

        (new SaveMessageService($flashManager))->addFailure(
            new RuntimeException('Could not save.')
        );
    }
}
