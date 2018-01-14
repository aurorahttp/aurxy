<?php

namespace Panlatent\Aurxy;

use Interop\Http\Server\ResponseHandlerInterface;
use Panlatent\Http\Server\FilterInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Filter implements FilterInterface
{
    public function process(ResponseInterface $response, ResponseHandlerInterface $handler): ResponseInterface
    {

    }
}