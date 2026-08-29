<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait IndexExpiredTrait
{
    public function indexExpired(Request $request, Response $response): Response
    {
        return $this->index($request, $response, 'expired');
    }
}
