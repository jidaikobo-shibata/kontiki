<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Renderers;

use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Renderers\TableRenderer;
use Jidaikobo\Kontiki\Services\AdminUrlGenerator;
use PHPUnit\Framework\TestCase;
use Slim\Views\PhpRenderer;

final class TableRendererTest extends TestCase
{
    public function testRenderForModelRestoresLegacyModelState(): void
    {
        $legacyModel = $this->createMock(ModelInterface::class);
        $operationModel = $this->createMock(ModelInterface::class);
        $renderer = new class ($this->createMock(PhpRenderer::class)) extends TableRenderer {
            /**
             * @param array<int, array<string, mixed>> $data
             * @param array<mixed> $routes
             */
            public function render(
                array $data,
                string $adminDirName,
                array $routes = [],
                string $context = 'all'
            ): string {
                return 'rendered';
            }

            public function currentModel(): ?ModelInterface
            {
                return $this->model;
            }
        };
        $renderer->setModel($legacyModel);

        self::assertSame(
            'rendered',
            $renderer->renderForModel($operationModel, [], 'post')
        );
        self::assertSame($legacyModel, $renderer->currentModel());
    }

    public function testRenderForModelRestoresStateAfterException(): void
    {
        $legacyModel = $this->createMock(ModelInterface::class);
        $operationModel = $this->createMock(ModelInterface::class);
        $renderer = new class ($this->createMock(PhpRenderer::class)) extends TableRenderer {
            /**
             * @param array<int, array<string, mixed>> $data
             * @param array<mixed> $routes
             */
            public function render(
                array $data,
                string $adminDirName,
                array $routes = [],
                string $context = 'all'
            ): string {
                throw new \RuntimeException('Rendering failed.');
            }

            public function currentModel(): ?ModelInterface
            {
                return $this->model;
            }
        };
        $renderer->setModel($legacyModel);

        try {
            $renderer->renderForModel($operationModel, [], 'post');
            self::fail('Expected rendering to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Rendering failed.', $exception->getMessage());
        }
        self::assertSame($legacyModel, $renderer->currentModel());
    }

    public function testActionLinksUseInjectedAdminBasePath(): void
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->method('getAttributes')->willReturn(['is_previewable' => false]);
        $renderer = new class (
            $view,
            new AdminUrlGenerator('/cms/admin')
        ) extends TableRenderer {
            /** @param array<string, mixed> $row */
            public function actions(array $row): string
            {
                $this->adminDirName = 'post';
                $this->deleteType = 'hardDelete';
                return $this->renderActions($row);
            }
        };

        $html = $renderer->actions(['id' => 10]);

        self::assertStringContainsString('/cms/admin/post/edit/10', $html);
        self::assertStringContainsString('/cms/admin/post/delete/10', $html);
    }
}
