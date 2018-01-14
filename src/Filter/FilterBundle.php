<?php

namespace Panlatent\Aurxy\Filter;

use Panlatent\Aurxy\Bundle;
use Panlatent\Http\Server\FilterInterface;
use SplPriorityQueue;

class FilterBundle extends Bundle
{
    /**
     * @var SplPriorityQueue|FilterInterface
     */
    protected $filters;

    /**
     * FilterBundle constructor.
     */
    public function __construct()
    {
        $this->filters = new SplPriorityQueue();
    }

    /**
     * @param FilterInterface $filter
     * @param int             $priority
     */
    public function addFilter(FilterInterface $filter, $priority = 0)
    {
        $this->filters->insert($filter, $priority);
    }

    /**
     * @param array $values
     */
    public function addFilters(array $values)
    {
        foreach ($values as $value) {
            if ( ! is_array($value)) {
                $this->filters->insert($value, 1);
            } else {
                $this->filters->insert($value[1], $value[0]);
            }
        }
    }

    /**
     * @return SplPriorityQueue
     */
    public function getFilters(): SplPriorityQueue
    {
        return $this->filters;
    }

    /**
     * @param SplPriorityQueue $filters
     */
    public function setFilters(SplPriorityQueue $filters)
    {
        $this->filters = $filters;
    }


}