<?php

namespace Aurxy\Filter;

use Panlatent\Http\Server\RequestFilterInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestFilterBundle extends FilterBundle implements RequestFilterInterface
{
    public function process(ServerRequestInterface $request): ServerRequestInterface
    {
        foreach ($this->filters as $filter) {
            if ($filter instanceof RequestFilterInterface) {
                $request = $filter->process($request);
            }
        }

        return $request;
    }
}