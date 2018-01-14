<?php

namespace Panlatent\Aurxy;

use Interop\Http\Server\MiddlewareInterface;

abstract class Middleware implements MiddlewareInterface, PriorityInterface
{
    use PriorityTrait;
}