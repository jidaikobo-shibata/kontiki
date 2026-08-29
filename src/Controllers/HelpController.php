<?php

namespace Jidaikobo\Kontiki\Controllers;

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HelpController extends BaseController
{
    public static function registerRoutes(App $app, string $basePath = ''): void
    {
        $app->get('/help', HelpController::class . ':showHelp');
        $app->get('/help/markdown', HelpController::class . ':showHelpMarkdown');
    }

    /**
     * help
     *
     * @return Response
     */
    public function showHelp(Request $request, Response $response): Response
    {
        ob_start();
        require(__DIR__ . '/../locale/' . env('APPLANG') . '/file/help.php');
        $content = ob_get_clean();

        return $this->renderResponse(
            $response,
            __('help'),
            $content,
            'layout-help.php'
        );
    }

    /**
     * show help of Markdown
     *
     * @return Response
     */
    public function showHelpMarkdown(Request $request, Response $response): Response
    {
        $helpText = __DIR__ . '/../locale/' . env('APPLANG') . '/file/markdown-help.php';
        $content = file_get_contents($helpText);

        return $this->renderResponse(
            $response,
            __("markdown_help", 'Markdown Help'),
            $content,
            'layout-help.php'
        );
    }
}
