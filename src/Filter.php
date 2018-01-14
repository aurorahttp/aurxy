<?php

namespace Panlatent\Aurxy;

use Panlatent\Http\Server\FilterInterface;

abstract class Filter implements FilterInterface, PriorityInterface
{
    use PriorityTrait;

    const PRIORITY_MIN = 1;
    const PRIORITY_MAX = 65535;
}