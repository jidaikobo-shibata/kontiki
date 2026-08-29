<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Runtime;

use Closure;

final class PerformanceReporter
{
    /** @var Closure(string): void */
    private Closure $logger;

    /** @var Closure(): float */
    private Closure $clock;

    /**
     * @param null|callable(string): void $logger
     * @param null|callable(): float $clock
     */
    public function __construct(?callable $logger = null, ?callable $clock = null)
    {
        $this->logger = Closure::fromCallable(
            $logger ?? static function (string $message): void {
                error_log($message);
            }
        );
        $this->clock = Closure::fromCallable(
            $clock ?? static fn(): float => microtime(true)
        );
    }

    public function report(float $startedAt, bool $enabled = false): void
    {
        if (!$enabled) {
            return;
        }

        $elapsedTime = ($this->clock)() - $startedAt;
        ($this->logger)(
            'Total execution time: ' . number_format($elapsedTime, 6) . ' seconds'
        );
    }
}
