<?php

namespace Aurxy\Filter;

use Psr\Http\Message\ResponseInterface;

class ResponseHeaderFilter extends ResponseFilter
{
    public function process(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}