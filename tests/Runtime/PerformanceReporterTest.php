<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Runtime;

use Jidaikobo\Kontiki\Runtime\PerformanceReporter;
use PHPUnit\Framework\TestCase;

final class PerformanceReporterTest extends TestCase
{
    public function testDoesNotLogWhenDisabled(): void
    {
        $messages = [];
        $reporter = new PerformanceReporter(
            static function (string $message) use (&$messages): void {
                $messages[] = $message;
            },
            static fn(): float => 12.0
        );

        $reporter->report(10.0);

        self::assertSame([], $messages);
    }

    public function testLogsElapsedTimeWithLegacyFormatWhenEnabled(): void
    {
        $messages = [];
        $reporter = new PerformanceReporter(
            static function (string $message) use (&$messages): void {
                $messages[] = $message;
            },
            static fn(): float => 12.5
        );

        $reporter->report(10.0, true);

        self::assertSame(
            ['Total execution time: 2.500000 seconds'],
            $messages
        );
    }
}
