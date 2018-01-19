<?php

namespace Aurxy;

use Panlatent\Http\Transaction\ProcessableInterface;

abstract class Filter extends \Panlatent\Http\Filter implements ProcessableInterface
{
    const PRIORITY_MIN = 1;
    const PRIORITY_MAX = 65535;

    public function canProcess(): bool
    {
        return true;
    }
}