<?php

namespace Jidaikobo\Kontiki\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and apply security headers.
     *
     * @param  Request                 $request
     * @param  RequestHandlerInterface $handler
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Delegate the request to the next middleware or controller
        $response = $handler->handle($request);

        // ホスト名を取得
        $host = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
            ? 'https://' . ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'))
            : 'http://' . ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        // Add security headers
        $headers = [
            "Content-Security-Policy" => "default-src 'self'; " .
                "script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com; " .
                "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                "font-src 'self' https://cdnjs.cloudflare.com; " .
                "img-src 'self' data:; " .
                "connect-src 'self'; " .
                "frame-src 'self';",
            "Strict-Transport-Security" => "max-age=31536000; includeSubDomains",
            "X-Content-Type-Options" => "nosniff",
            "Referrer-Policy" => "no-referrer-when-downgrade",
            "X-XSS-Protection" => "1; mode=block",
            "Permissions-Policy" => "geolocation=(), microphone=(), camera=()",
            "X-Frame-Options" => "SAMEORIGIN",
            "Access-Control-Allow-Origin" => $host,
        ];

        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
