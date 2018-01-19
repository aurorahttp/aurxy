<?php

require __DIR__ . '/BaseAurxy.php';

use Symfony\Component\EventDispatcher\Event;

final class Aurxy extends BaseAurxy
{
    /**
     * Trigger an event.
     *
     * @param  string    $eventName
     * @param Event|null $event
     * @return Event
     */
    public static function event($eventName, Event $event = null)
    {
        return static::$event->dispatch($eventName, $event);
    }

    /**
     * Subscribe an event.
     *
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     */
    public static function on($eventName, $listener, $priority = 0)
    {
        static::$event->addListener($eventName, $listener, $priority);
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return bool Whether the record has been processed
     */
    public static function log($level, $message, array $context = [])
    {
        return static::$log->log($level, $message, $context);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function debug($message, array $context = [])
    {
        return static::$log->debug($message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function access($message, array $context = [])
    {
        return static::$log->info($message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function error($message, array $context = [])
    {
        return static::$log->error($message, $context);
    }
}