<?php

namespace Jidaikobo\Kontiki\Controllers\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait FrontendReadTrait
{
    public function frontendReadBySlug(Request $request, Response $response, array $args): Response
    {
        $slug = $args['slug'];
        $this->context = 'normal';
        $query = $this->model->buildSearchConditions();
        $query = $this->model->getAdditionalConditions($query, $this->context);
        $query->where('slug', '=', $slug);

        $data = $query->get()
                      ->map(fn($item) => (array) $item)
                      ->toArray();

        $data = [
            'body' => $data[0],
        ];

        return $this->jsonResponse($response, $data);
    }
}
