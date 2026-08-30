<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Middleware;

use Jidaikobo\Kontiki\Core\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;
use Slim\Views\PhpRenderer;

final class AdminAuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Auth $auth,
        private PhpRenderer $view
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->auth->isAdminLoggedIn()) {
            return $handler->handle($request);
        }

        $response = (new SlimResponse())
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(404);
        $content = $this->view->fetch('error/404.php');

        return $this->view->render($response, 'layout-error.php', [
            'lang' => env('APPLANG', 'en'),
            'pageTitle' => __('404_text'),
            'content' => $content,
        ]);
    }
}
