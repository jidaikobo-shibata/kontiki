<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Handlers\FormHandler;
use Jidaikobo\Kontiki\Handlers\TableHandler;
use Jidaikobo\Kontiki\Models\ModelInterface;
use Jidaikobo\Kontiki\Renderers\FormRenderer;
use Jidaikobo\Kontiki\Renderers\TableRenderer;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\TableService;
use PHPUnit\Framework\TestCase;
use Slim\Views\PhpRenderer;

final class ViewOperationServiceTest extends TestCase
{
    public function testFormServiceRendersFieldsAsOneOperation(): void
    {
        $fields = ['title' => ['type' => 'text']];
        $formVars = ['buttonID' => 'submit'];
        $renderer = $this->createMock(FormRenderer::class);
        $renderer->expects(self::once())->method('renderFields')
            ->with($fields)->willReturn('<input>');
        $view = $this->createMock(PhpRenderer::class);
        $view->expects(self::once())->method('addAttribute')
            ->with('formVars', $formVars);
        $view->expects(self::once())->method('fetch')
            ->with('forms/edit.php', [
                'actionAttribute' => '/save',
                'csrfToken' => 'token',
                'formHtml' => '<input>',
            ])->willReturn('<form>rendered</form>');
        $service = new FormService(
            $renderer,
            $this->createMock(FormHandler::class),
            $view
        );

        self::assertSame(
            '<form>rendered</form>',
            $service->formHtml('/save', $fields, 'token', $formVars)
        );
    }

    public function testFormServiceDecoratesAsOneOperation(): void
    {
        $model = $this->createMock(ModelInterface::class);
        $handler = $this->createMock(FormHandler::class);
        $handler->expects(self::once())->method('decorate')
            ->with('<form></form>', $model, ['error'], ['success'])
            ->willReturn('<form>decorated</form>');
        $service = new FormService(
            $this->createMock(FormRenderer::class),
            $handler,
            $this->createMock(PhpRenderer::class)
        );

        self::assertSame(
            '<form>decorated</form>',
            $service->addMessages(
                '<form></form>',
                ['error'],
                ['success'],
                $model
            )
        );
    }

    public function testTableServiceRendersForExplicitModel(): void
    {
        $model = $this->createMock(ModelInterface::class);
        $data = [['id' => 1]];
        $routes = [['path' => '/post/create']];
        $renderer = $this->createMock(TableRenderer::class);
        $renderer->expects(self::once())->method('renderForModel')
            ->with($model, $data, 'post', $routes, 'published')
            ->willReturn('<table></table>');
        $service = new TableService(
            $renderer,
            $this->createMock(TableHandler::class),
            $this->createMock(PhpRenderer::class)
        );

        self::assertSame(
            '<table></table>',
            $service->tableHtml($data, 'post', $routes, 'published', $model)
        );
    }

    public function testTableServiceDecoratesAsOneOperation(): void
    {
        $model = $this->createMock(ModelInterface::class);
        $handler = $this->createMock(TableHandler::class);
        $handler->expects(self::once())->method('decorate')
            ->with('<table></table>', $model, ['error'], ['success'])
            ->willReturn('<div>messages</div><table></table>');
        $service = new TableService(
            $this->createMock(TableRenderer::class),
            $handler,
            $this->createMock(PhpRenderer::class)
        );

        self::assertSame(
            '<div>messages</div><table></table>',
            $service->addMessages(
                '<table></table>',
                ['error'],
                ['success'],
                $model
            )
        );
    }
}
