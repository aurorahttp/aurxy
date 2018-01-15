<?php

use Panlatent\Aurxy\Ev\SafeCallback;
use Panlatent\Aurxy\Server;
use Panlatent\Aurxy\Server\SocketContainer;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

defined('AURXY_DIR') or define('AURXY_DIR', dirname(__DIR__));
defined('AURXY_CONFIG_FILENAME') or define('AURXY_CONFIG_FILENAME', 'aurxy.yml');

abstract class BaseAurxy
{
    const NAME = 'aurxy';
    const VERSION = '0.1.0';
    /**
     * @var EventDispatcher
     */
    public static $event;
    /**
     * @var OptionsResolver
     */
    public static $options;
    /**
     * @var EvWatcher[]
     */
    public static $watchers;
    /**
     * @var Server
     */
    public static $server;

    /**
     * @var string
     */
    public static $bootstrap;

    /**
     * Aurxy constructor.
     */
    final private function __construct()
    {
        // Not allow instance this class.
    }

    /**
     * Init base environment and run server.
     *
     * Use kill -USR2 will call bootstrap method.
     */
    public static function run()
    {
        static::$watchers[] = new EvSignal(SIGKILL, SafeCallback::wrapper(function () {
            static::shutdown();
        }));
        static::$watchers[] = new EvSignal(SIGTERM, SafeCallback::wrapper(function () {
            static::shutdown();
        }));
        static::$watchers[] = new \EvSignal(SIGUSR2, SafeCallback::wrapper(function() {
            static::bootstrap();
            echo "Reload bootstrap\n";
        }));

        static::bootstrap();

        $sockets = new SocketContainer((array)static::$options['server']['listen']);
        static::$server = new Server($sockets);
        static::$server->start();

        Ev::run(Ev::FLAG_AUTO);
    }

    /**
     * Stop server and exit.
     */
    public static function shutdown()
    {
        static::$server->stop();
        Ev::stop();

        exit(0);
    }

    /**
     * @param array $options
     */
    public static function configure($options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(static::getDefaultOptions());
        static::$options = $resolver->resolve($options);
    }

    /**
     * Init event dispatcher and load bootstrap file.
     *
     * If again call this method, will lose old event subscribes.
     */
    public static function bootstrap()
    {
        static::$event = new EventDispatcher();
        if (! empty(static::$bootstrap) && is_file(static::$bootstrap)) {
            require(static::$bootstrap);
        }
    }

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
     * @return array
     */
    public static function getDefaultOptions()
    {
        return [
            'server' => [
                'listen' => [
                    '127.0.0.1:10085',
                ],
            ],
        ];
    }
}