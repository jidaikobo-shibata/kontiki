<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Renderers;

use Jidaikobo\Kontiki\Renderers\FormRenderer;
use Jidaikobo\Kontiki\Utils\FormUtils;
use PHPUnit\Framework\TestCase;
use Slim\Views\PhpRenderer;

final class FormRendererTest extends TestCase
{
    public function testRenderFieldsDoesNotOverwriteLegacyFieldState(): void
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->method('getAttributes')->willReturn([]);
        $view->method('fetch')->willReturnCallback(
            static function (string $template): string {
                return match ($template) {
                    'forms/fields/text.php' => '<input>',
                    'forms/fieldset/flat.php' => '<fieldset></fieldset>',
                    'forms/groups/main.php' => '<div>group</div>',
                    default => '',
                };
            }
        );
        $renderer = new FormRenderer($view, new FormUtils());
        $renderer->setFields([]);

        $rendered = $renderer->renderFields([
            'title' => [
                'type' => 'text',
                'group' => 'main',
                'label' => 'Title',
            ],
        ]);

        self::assertSame('<div>group</div>', $rendered);
        self::assertSame('', $renderer->render());
    }
}
