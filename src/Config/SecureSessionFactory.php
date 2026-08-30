<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use Aura\Session\Session;
use Aura\Session\SessionFactory;

final class SecureSessionFactory
{
    public function __construct(private bool $secure = false)
    {
    }

    /** @param array<string, string> $cookies */
    public function create(array $cookies, string $requestUri = ''): Session
    {
        $sessionStarted = session_status() === PHP_SESSION_ACTIVE;
        if (!$sessionStarted) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
        }

        if (
            str_contains($requestUri, '.js')
            || str_contains($requestUri, '.css')
            || str_contains($requestUri, '.ico')
        ) {
            session_cache_limiter('private_no_expire');
        }

        $session = (new SessionFactory())->newInstance($cookies);
        if (!$sessionStarted) {
            $session->setCookieParams([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $this->secure,
            ]);
        }

        return $session;
    }
}
