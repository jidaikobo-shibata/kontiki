<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Jidaikobo\Kontiki\Services\ApplicationClock;
use PHPUnit\Framework\TestCase;

final class ApplicationClockTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testNowUsesTheApplicationTimezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 03:00:00', 'UTC'));

        self::assertSame(
            '2026-08-30 12:00:00 +09:00',
            (new ApplicationClock('Asia/Tokyo'))->now()->format('Y-m-d H:i:s P')
        );
        self::assertSame(
            '2026-08-30 03:00:00 +00:00',
            (new ApplicationClock('Asia/Tokyo'))->nowUtc()->format('Y-m-d H:i:s P')
        );
    }

    public function testItConvertsBetweenLocalTimeAndUtc(): void
    {
        $clock = new ApplicationClock('Asia/Tokyo');

        self::assertSame(
            '2026-08-30 03:00:00',
            $clock->localToUtc('2026-08-30 12:00:00')->format('Y-m-d H:i:s')
        );
        self::assertSame(
            '2026-08-30 12:00:00',
            $clock->utcToLocal('2026-08-30 03:00:00')->format('Y-m-d H:i:s')
        );
    }

    public function testItRejectsAnInvalidTimezone(): void
    {
        $this->expectException(Exception::class);

        new ApplicationClock('Not/A_Timezone');
    }
}
