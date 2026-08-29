<?php

namespace Jidaikobo\Kontiki\Controllers;

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

class AdminController
{
    private PhpRenderer $view;

    public function __construct(PhpRenderer $view)
    {
        $this->view = $view;
    }

    public static function registerRoutes(App $app): void
    {
        $app->get('/kontiki-admin.js', AdminController::class . ':serveJs');
        $app->get('/kontiki-admin.css', AdminController::class . ':serveCss');
        $app->get('/favicon.ico', AdminController::class . ':serveFavicon');
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'js/kontiki-admin.js.php',
            [
                'skip_to_main_content' => __('skip_to_main_content'),
                'skip_to_navigation' => __('skip_to_navigation'),
                'publishing' => __('publishing'),
                'reserved' => __('reserved'),
                'expired' => __('expired'),
                'do_publish' => __('do_publish', 'publish'),
                'do_reserve' => __('do_reserve', 'reserve'),
                'do_save_as_pending' => __('do_save_as_pending', 'save as pending'),
                'do_save_as_draft' => __('do_save_as_draft', 'save as draft'),
                'published_at' => __('published_at'),
                'published_url' => __('published_url'),
                'reserved_url' => __('reserved_url'),
                'banned_url' => __('banned_url'),
                'open_in_new_window' => __('open_in_new_window'),
                'open_sidebar' => __('open_sidebar'),
                'close_sidebar' => __('close_sidebar'),
            ]
        );
        $response->getBody()->write($content);
        return $response->withHeader(
            'Content-Type',
            'application/javascript; charset=utf-8'
        )->withStatus(200);
    }

    /**
     * Serve the requested CSS file.
     *
     * @return Response
     */
    public function serveCss(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'css/kontiki-admin.css.php',
            [
                'color' => env('ADMIN_THEME_COLOR', '#ffffff'),
                'bgcolor' => env('ADMIN_THEME_BGCOLOR', '#343a40')
            ]
        );
        $response->getBody()->write($content);
        return $response->withHeader(
            'Content-Type',
            'text/css; charset=utf-8'
        )->withStatus(200);
    }

    /**
     * Serve the requested favicon file.
     *
     * @return Response
     */
    public function serveFavicon(Request $request, Response $response): Response
    {
        $faviconPath = env('PROJECT_PATH', '') . '/src/views/images/favicon.ico';
        $content = file_get_contents($faviconPath);
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'image/x-icon')
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withStatus(200);
    }
}
