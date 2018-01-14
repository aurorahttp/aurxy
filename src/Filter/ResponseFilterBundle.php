<?php

namespace Panlatent\Aurxy\Filter;

use Panlatent\Http\Server\ResponseFilterInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFilterBundle extends FilterBundle implements ResponseFilterInterface
{
    public function process(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->filters as $filter) {
            if ($filter instanceof ResponseFilterInterface) {
                $response = $filter->process($response);
            }
        }

        return $response;
    }
}