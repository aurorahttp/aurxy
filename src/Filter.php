<?php

namespace Panlatent\Aurxy;

abstract class Filter extends \Panlatent\Http\Filter
{
    const PRIORITY_MIN = 1;
    const PRIORITY_MAX = 65535;

    public function canProcess(): bool
    {
        return true;
    }
}