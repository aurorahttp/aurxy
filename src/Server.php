<?php

namespace Panlatent\Aurxy;

use Ev;
use EvIo;
use EvSignal;
use EvStat;
use Panlatent\Aurxy\Ev\SafeCallback;

class Server
{
    public function run()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_nonblock($socket);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, '127.0.0.1', 10085);
        socket_listen($socket);

        $socketEv = new EvIo($socket, Ev::READ, new SafeCallback(function (EvIo $watcher) use ($socket) {
            $client = socket_accept($socket);
            $connection = new \Panlatent\Aurxy\Connection($client);
            $connection->handle();
        }));

        $signalEv = new EvSignal(SIGKILL, function ($watcher) {
            /** @var EvSignal $watcher */
            echo "Kill me(kill)!\n";
            $watcher->stop();
            exit(0);
        });

        $signalEv = new EvSignal(SIGTERM, function ($watcher) use ($socket) {
            /** @var EvSignal $watcher */
            echo "Kill me(term)!\n";
            $watcher->stop();
            socket_close($socket);

            exit(0);
        });

        $fileWatcher = new EvStat(__FILE__, 5.0, function ($watcher) use ($socket) {
            /** @var EvStat $watcher */
            echo "Program changed!\n";
        });

        Ev::run(Ev::FLAG_AUTO);
    }
}