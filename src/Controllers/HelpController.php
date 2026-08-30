<?php

namespace Jidaikobo\Kontiki\Controllers;

use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\HelpContentService;
use Jidaikobo\Kontiki\Services\RoutesService;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

class HelpController extends BaseController
{
    private HelpContentService $helpContentService;

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        ?CsrfValidationService $csrfValidationService = null,
        ?HelpContentService $helpContentService = null
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService,
            $csrfValidationService
        );
        $this->helpContentService = $helpContentService ?? new HelpContentService(
            __DIR__ . '/../locale',
            env('APPLANG', 'en')
        );
    }

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
        return $this->renderResponse(
            $response,
            __('help'),
            $this->helpContentService->renderHelp(),
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
        return $this->renderResponse(
            $response,
            __("markdown_help", 'Markdown Help'),
            $this->helpContentService->readMarkdownHelp(),
            'layout-help.php'
        );
    }
}
