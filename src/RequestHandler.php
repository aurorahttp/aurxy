<?php

namespace Panlatent\Aurxy;

use GuzzleHttp\Psr7\Response;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $content = file_get_contents($request->getUri());

        return new Response(200, [], $content);
    }
}