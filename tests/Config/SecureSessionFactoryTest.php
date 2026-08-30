<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use Jidaikobo\Kontiki\Config\SecureSessionFactory;
use PHPUnit\Framework\TestCase;

final class SecureSessionFactoryTest extends TestCase
{
    /** @var array<string, int|string|bool> */
    private array $originalCookieParams;
    private string $originalStrictMode;
    private string $originalOnlyCookies;

    protected function setUp(): void
    {
        $this->originalCookieParams = session_get_cookie_params();
        $this->originalStrictMode = (string) ini_get('session.use_strict_mode');
        $this->originalOnlyCookies = (string) ini_get('session.use_only_cookies');
    }

    protected function tearDown(): void
    {
        session_set_cookie_params($this->originalCookieParams);
        ini_set('session.use_strict_mode', $this->originalStrictMode);
        ini_set('session.use_only_cookies', $this->originalOnlyCookies);
    }

    public function testCreatesSessionWithDefensiveCookieSettings(): void
    {
        $session = (new SecureSessionFactory())->create([]);
        $params = $session->getCookieParams();

        self::assertTrue($params['httponly']);
        self::assertSame('Lax', $params['samesite']);
        self::assertSame('1', ini_get('session.use_strict_mode'));
        self::assertSame('1', ini_get('session.use_only_cookies'));
    }
}
