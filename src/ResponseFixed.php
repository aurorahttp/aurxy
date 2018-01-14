<?php

namespace Panlatent\Aurxy;

use Panlatent\Aurxy\Filter\ResponseFilter;
use Psr\Http\Message\ResponseInterface;

class ResponseFixed extends ResponseFilter
{
    public function process(ResponseInterface $response): ResponseInterface
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