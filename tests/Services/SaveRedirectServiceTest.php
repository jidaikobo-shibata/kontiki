<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\SaveRedirectService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SaveRedirectServiceTest extends TestCase
{
    private SaveRedirectService $service;

    protected function setUp(): void
    {
        $this->service = new SaveRedirectService();
    }

    public function testPreviewRequiresExactLegacyStringFlag(): void
    {
        self::assertSame('/post/preview', $this->service->previewTarget(['preview' => '1'], 'post'));
        self::assertNull($this->service->previewTarget(['preview' => 1], 'post'));
        self::assertNull($this->service->previewTarget([], 'post'));
    }

    /**
     * @return iterable<string, array{string, ?int, string}>
     */
    public static function formTargetProvider(): iterable
    {
        yield 'create' => ['create', null, '/post/create'];
        yield 'edit' => ['edit', 42, '/post/edit/42'];
    }

    #[DataProvider('formTargetProvider')]
    public function testReturnsExistingFormTargets(
        string $context,
        ?int $id,
        string $expected
    ): void {
        self::assertSame($expected, $this->service->formTarget($context, 'post', $id));
    }

    public function testReturnsSavedAndIndexTargets(): void
    {
        self::assertSame('/post/edit/42', $this->service->savedTarget('post', 42));
        self::assertSame('/post/index', $this->service->indexTarget('post'));
    }
}
