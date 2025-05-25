<?php

namespace App\Domain\Middleware;

use Slim\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class CsrfCheckMiddleware implements MiddlewareInterface
{
    private const _METHODS = ['POST'];
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): Response|ResponseInterface
    {
        $method = $request->getMethod();
        if (!in_array(strtoupper($method), self::_METHODS)) {
            return $handler->handle($request);
        }

        $params = (array) $request->getParsedBody();
        $token = $params['csrf_token'] ?? '';

        if (
            !isset($_SESSION['csrf_token']) ||
            !is_string($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            // echo "<pre>";
            // var_dump($token, $_SESSION['csrf_token']);
            // // die;
            $response = new Response();
            $response->getBody()->write('CSRF validation failed.');
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }
}
