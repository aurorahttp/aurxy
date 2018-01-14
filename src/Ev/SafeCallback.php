<?php

namespace Panlatent\Aurxy\Ev;

class SafeCallback
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * SafeCallback constructor.
     *
     * @param callable $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param \EvWatcher $watcher
     */
    public function __invoke($watcher)
    {
        try {
            call_user_func_array($this->callback, func_get_args());
        } catch (\Throwable $exception) {
            die(sprintf("Error class %s #%d \"%s\" in %s at %d line",
                get_class($exception),
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }
    }
}