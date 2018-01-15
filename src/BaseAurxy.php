<?php

use Panlatent\Aurxy\Ev\SafeCallback;
use Panlatent\Aurxy\Server;
use Panlatent\Aurxy\Server\SocketContainer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

defined('AURXY_DIR') or define('AURXY_DIR', dirname(__DIR__));
defined('AURXY_CONFIG_FILENAME') or define('AURXY_CONFIG_FILENAME', 'aurxy.yml');

class BaseAurxy
{
    const NAME = 'aurxy';
    const VERSION = '0.1.0';
    /**
     * @var EventDispatcher
     */
    public static $dispatcher;
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
     * Aurxy constructor.
     */
    final private function __construct()
    {
        // Not allow instance this class.
    }

    public static function run()
    {
        static::$dispatcher = new EventDispatcher();
        $sockets = new SocketContainer((array)static::$options['server']['listen']);
        static::$server = new Server($sockets);
        static::$server->start();

        static::$watchers[] = new EvSignal(SIGKILL, SafeCallback::wrapper(function () {
            static::shutdown();
        }));
        static::$watchers[] = new EvSignal(SIGTERM, SafeCallback::wrapper(function () {
            static::shutdown();
        }));

        Ev::run(Ev::FLAG_AUTO);
    }

    public static function shutdown()
    {
        static::$server->stop();
        Ev::stop();

        exit(0);
    }

    public static function configure($options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(static::getDefaultOptions());
        static::$options = $resolver->resolve($options);
    }

    public static function getDefaultOptions()
    {
        return [
            'server' => [
                'listen' => [
                    '127.0.0.1:10085'
                ]
            ],
        ];
    }
}