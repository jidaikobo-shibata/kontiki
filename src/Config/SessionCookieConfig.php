<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use InvalidArgumentException;

final readonly class SessionCookieConfig
{
    public function __construct(public bool $secure)
    {
    }

    public static function resolve(string $configuredValue, string $baseUrl): self
    {
        $configuredValue = strtolower(trim($configuredValue));
        if ($configuredValue === '') {
            return new self(parse_url($baseUrl, PHP_URL_SCHEME) === 'https');
        }

        if (in_array($configuredValue, ['true', '1'], true)) {
            return new self(true);
        }
        if (in_array($configuredValue, ['false', '0'], true)) {
            return new self(false);
        }

        throw new InvalidArgumentException(
            'SESSION_COOKIE_SECURE must be true, false, 1, or 0.'
        );
    }
}
