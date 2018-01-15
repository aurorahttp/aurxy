<?php

namespace Panlatent\Aurxy;

use Ev;
use EvIo;
use Panlatent\Aurxy\Ev\SafeCallback;
use Panlatent\Aurxy\Server\SocketContainer;

class Server
{
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
        $connection = new Connection($client);
        $connection->handle();
    }
}