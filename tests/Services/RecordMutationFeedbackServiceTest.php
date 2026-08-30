<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Services\RecordMutationFeedbackConfig;
use Jidaikobo\Kontiki\Services\RecordMutationFeedbackService;
use Jidaikobo\Kontiki\Services\RecordMutationResult;
use PHPUnit\Framework\TestCase;

final class RecordMutationFeedbackServiceTest extends TestCase
{
    private function config(): RecordMutationFeedbackConfig
    {
        return new RecordMutationFeedbackConfig(
            'widget',
            'test_mutation_success',
            ':name removed.',
            'test_mutation_failure',
            'Could not remove :name.',
            '/widget/index',
            '/widget/edit/42'
        );
    }

    public function testAddsSuccessAndReturnsSuccessTarget(): void
    {
        $flash = $this->createMock(FlashManager::class);
        $flash->expects(self::once())->method('addMessage')
            ->with('success', 'Widget removed.');
        $flash->expects(self::never())->method('addErrors');

        $target = (new RecordMutationFeedbackService($flash))->apply(
            RecordMutationResult::success(),
            $this->config()
        );

        self::assertSame('/widget/index', $target);
    }

    public function testAddsErrorOnlyForExceptionFailure(): void
    {
        $flash = $this->createMock(FlashManager::class);
        $flash->expects(self::never())->method('addMessage');
        $flash->expects(self::once())->method('addErrors')
            ->with(['Could not remove Widget.']);

        $target = (new RecordMutationFeedbackService($flash))->apply(
            RecordMutationResult::failure(
                RecordMutationResult::FAILURE_EXCEPTION
            ),
            $this->config()
        );

        self::assertSame('/widget/edit/42', $target);
    }

    public function testReturnsFailureTargetWithoutMessageForFalseOperation(): void
    {
        $flash = $this->createMock(FlashManager::class);
        $flash->expects(self::never())->method('addMessage');
        $flash->expects(self::never())->method('addErrors');

        $target = (new RecordMutationFeedbackService($flash))->apply(
            RecordMutationResult::failure(
                RecordMutationResult::FAILURE_OPERATION
            ),
            $this->config()
        );

        self::assertSame('/widget/edit/42', $target);
    }
}
