<?php

namespace Jidaikobo\Kontiki\Middleware;

use Jidaikobo\Kontiki\Core\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\PhpRenderer;
use Slim\Routing\RouteParser;

class AuthMiddleware implements MiddlewareInterface
{
    private array $excludedRoutes = [
        '/favicon.ico',
        '/login',
        '/logout',
    ];

    public function __construct(
        private PhpRenderer $view,
        private Auth $auth,
        private RouteParser $routeParser,
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $requestedPath = $request->getUri()->getPath();
        $path = '/' . basename($requestedPath);

        // for guest routes
        if (in_array($path, $this->excludedRoutes, true)) {
            return $handler->handle($request);
        }

        // for login users
        if (!$this->auth->isLoggedIn()) {
            $redirect = substr($requestedPath, strlen(env('BASEPATH', '')));
            $loginUrl = $this->routeParser->urlFor('login', [], ['redirect' => $redirect]);

            // Check the referrer and redirect to login as it is an internal transition
            $referer = $request->getHeaderLine('Referer');
            if (strpos($referer, $_SERVER['HTTP_HOST']) !== false) {
                return (new \Slim\Psr7\Response())
                    ->withHeader('Location', $loginUrl)
                    ->withStatus(302);
            }

            // If an external access is suspected, return 404.
            $response = new \Slim\Psr7\Response();
            $content = $this->view->fetch('error/404.php');
            return $this->view->render(
                $response->withHeader('Content-Type', 'text/html')->withStatus(404),
                'layout-error.php',
                [
                    'lang' => env('APPLANG', 'en'),
                    'pageTitle' => __('404_text'),
                    'content' => $content
                ]
            );
        }

        return $handler->handle($request);
    }
}
