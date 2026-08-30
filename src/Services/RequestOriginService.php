<?php

namespace Jidaikobo\Kontiki\Services;

use Psr\Http\Message\ServerRequestInterface;

class RequestOriginService
{
    public function hasInternalReferer(ServerRequestInterface $request): bool
    {
        $refererHost = parse_url($request->getHeaderLine('Referer'), PHP_URL_HOST);
        if (!is_string($refererHost) || $refererHost === '') {
            return false;
        }

        $requestHost = $request->getUri()->getHost();
        if ($requestHost === '') {
            $requestHost = $this->hostFromHeader($request->getHeaderLine('Host'));
        }

        return $requestHost !== '' && strcasecmp($refererHost, $requestHost) === 0;
    }

    private function hostFromHeader(string $hostHeader): string
    {
        $host = parse_url('http://' . $hostHeader, PHP_URL_HOST);
        return is_string($host) ? $host : '';
    }
}
