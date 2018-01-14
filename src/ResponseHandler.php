<?php

namespace Panlatent\Aurxy;

use Interop\Http\Server\ResponseHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseHandler implements ResponseHandlerInterface
{
    public function handle(ResponseInterface $response): ResponseInterface
    {
        if ($response->hasHeader('Transfer-Encoding')) {
            /*
             * Transfer-Encoding header need set a size to body first line.
             * Now working method not support Transfer-Encoding header.
             */
            $response = $response->withoutHeader('Transfer-Encoding');
        }

        return $response;
    }
}