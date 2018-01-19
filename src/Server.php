<?php

namespace Aurxy;

use Aurxy;
use Ev;
use EvIo;
use Aurxy\Ev\SafeCallback;
use Aurxy\Server\SocketContainer;

class Server
{
    const EVENT_CLIENT_CONNECT_BEFORE = 'server.client_connect::before';
    const EVENT_CLIENT_CONNECT_AFTER = 'server.client_connect::after';
    /**
     * @var array|SocketContainer
     */
    protected $sockets;
    /**
     * @var array|EvIo[]
     */
    protected $socketReadWatchers = [];

    /**
     * Server constructor.
     *
     * @param array|SocketContainer $sockets
     */
    public function __construct($sockets)
    {
        $this->sockets = $sockets;
    }

    public function start()
    {
        foreach ($this->sockets as $socket) {
            $this->socketReadWatchers[] = new EvIo(
                $socket,
                Ev::READ,
                SafeCallback::wrapper(
                    function () use($socket) {
                        $this->onSocketRead($socket);
                    }
                )
            );
        }
    }

    public function stop()
    {
        foreach ($this->socketReadWatchers as $watcher) {
            $watcher->stop();
        }
        foreach ($this->sockets as $socket) {
            socket_shutdown($socket);
            socket_close($socket);
        }
    }

    public function onSocketRead($socket)
    {
        $client = socket_accept($socket);
        Aurxy::event(static::EVENT_CLIENT_CONNECT_BEFORE);
        $connection = new Connection($client);
        Aurxy::event(static::EVENT_CLIENT_CONNECT_AFTER);
        $connection->handle();
    }
}