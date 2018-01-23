<?php

namespace Aurxy;


abstract class Filter extends \Aurora\Http\Transaction\Filter
{
    const PRIORITY_MIN = 1;
    const PRIORITY_MAX = 65535;
}