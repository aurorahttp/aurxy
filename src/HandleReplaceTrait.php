<?php

namespace Aurxy;

use Closure;

trait HandleReplaceTrait
{
    /**
     * @var callable
     */
    protected $replaceHandle;

    /**
     * Replace class handle method.
     *
     * @param callable $newHandle
     * @param bool     $bindTo
     */
    public function replaceHandle($newHandle, $bindTo = true)
    {
        if ($bindTo && $newHandle instanceof Closure) {
            $newHandle = $newHandle->bindTo($this, $this);
        }
        $this->replaceHandle = $newHandle;
    }
}