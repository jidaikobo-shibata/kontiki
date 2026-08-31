<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Middleware;

use Jidaikobo\Kontiki\Config\SessionCookieConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    private SessionCookieConfig $sessionCookieConfig;

    public function __construct(?SessionCookieConfig $sessionCookieConfig = null)
    {
        $this->sessionCookieConfig = $sessionCookieConfig
            ?? SessionCookieConfig::resolve(
                env('SESSION_COOKIE_SECURE', ''),
                env('BASEURL', '')
            );
    }

    /**
     * Process an incoming server request and apply security headers.
     *
     * @param  Request                 $request
     * @param  RequestHandlerInterface $handler
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        $headers = [
            "Content-Security-Policy" => "default-src 'self'; " .
                "script-src 'self'; " .
                "style-src 'self'; " .
                "font-src 'self'; " .
                "img-src 'self' data:; " .
                "connect-src 'self'; " .
                "frame-src 'self'; " .
                "object-src 'none'; " .
                "base-uri 'self'; " .
                "form-action 'self'; " .
                "frame-ancestors 'self';",
            "X-Content-Type-Options" => "nosniff",
            "Referrer-Policy" => "strict-origin-when-cross-origin",
            "X-XSS-Protection" => "1; mode=block",
            "Permissions-Policy" => "geolocation=(), microphone=(), camera=()",
            "X-Frame-Options" => "SAMEORIGIN",
        ];
        if ($this->sessionCookieConfig->secure) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
