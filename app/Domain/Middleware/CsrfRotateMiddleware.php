<?php

namespace App\Domain\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CsrfRotateMiddleware implements MiddlewareInterface
{
    private const _METHODS = ['POST'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (in_array(strtoupper($request->getMethod()), self::_METHODS)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $response;
    }
}
