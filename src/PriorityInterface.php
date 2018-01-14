<?php

namespace Panlatent\Aurxy;

interface PriorityInterface
{
    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param int $priority
     */
    public function setPriority($priority);
}