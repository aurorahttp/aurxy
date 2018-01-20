<?php

namespace Aurxy;

use Aurora\Http\Transaction\ProcessableInterface;

abstract class Filter extends \Aurora\Http\Transaction\Filter implements ProcessableInterface
{
    const PRIORITY_MIN = 1;
    const PRIORITY_MAX = 65535;

    public function canProcess(): bool
    {
        return true;
    }
}