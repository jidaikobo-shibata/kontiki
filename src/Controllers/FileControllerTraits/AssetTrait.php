<?php

namespace Jidaikobo\Kontiki\Controllers\FileControllerTraits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait AssetTrait
{
    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'js/kontiki-file.js.php',
            [
                'basepath' => env('BASEPATH', '')
            ]
        );
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveIndexJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'js/kontiki-file-index.js.php',
            [
                'get_file_list' => __('get_file_list'),
                'confirm_delete_message' => __('confirm_delete_message', 'The item will be permanently deleted. Are you sure you want to delete this item?'),
                'couldnt_find_file' => __('couldnt_find_file'),
                'couldnt_get_file_list' => __('couldnt_get_file_list'),
                'copied' => __('copied'),
                'copy_failed' => __('copy_failed'),
                'close' => __('close'),
                'edit' => __('edit'),
                'couldnt_delete_file' => __('couldnt_delete_file'),
                'insert_success' => __('insert_success'),
            ]
        );
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveCsrfJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch('js/kontiki-file-csrf.js.php');
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveLightboxJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch('js/kontiki-file-lightbox.js.php');
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveUtilsJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch('js/kontiki-file-utils.js.php');
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveUploaderJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'js/kontiki-file-uploader.js.php',
            [
                'uploading' => __('uploading'),
                'couldnt_upload' => __('couldnt_upload', "Could not upload"),
            ]
        );
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * Serve the requested Css file.
     *
     * @return Response
     */
    public function serveCss(Request $request, Response $response): Response
    {
        $content = $this->view->fetch('css/kontiki-file.css');
        $response->getBody()->write($content);
        return $response->withHeader(
            'Content-Type',
            'text/css; charset=utf-8'
        )->withStatus(200);
    }
}
