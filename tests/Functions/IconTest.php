<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Functions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class IconTest extends TestCase
{
    public function testRendersDecorativeIconFromTheLocalSprite(): void
    {
        self::assertSame(
            '<svg class="kontiki-icon nav-icon" aria-hidden="true" focusable="false">'
            . '<use href="#kontiki-icon-folder"></use></svg>',
            icon('folder', 'nav-icon')
        );
    }

    public function testEscapesAdditionalClasses(): void
    {
        self::assertStringContainsString(
            'class="kontiki-icon &quot; unsafe"',
            icon('folder', '" unsafe')
        );
    }

    public function testRejectsUnknownIconNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        icon('not-in-the-sprite');
    }
}
