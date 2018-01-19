<?php

namespace Aurxy\Ev;

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
     * @param callable $callback
     * @return static
     */
    public static function wrapper($callback)
    {
        return new static($callback);
    }

    /**
     * @param \EvWatcher $watcher
     */
    public function __invoke($watcher)
    {
        try {
            call_user_func_array($this->callback, func_get_args());
        } catch (\Throwable $exception) {
            echo sprintf("\n%s #%d \"%s\" in %s at at line %d\n",
                get_class($exception),
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );
            echo $exception->getTraceAsString();
            die(1);
        }
    }
}