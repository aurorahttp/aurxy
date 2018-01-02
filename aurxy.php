#!/usr/bin/env php
<?php
/*
 * Aurxy
 */

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_nonblock($socket);
socket_bind($socket, '127.0.0.1', 10086);
socket_listen($socket);

$ev = new EvIo($socket, Ev::READ, function() use($socket) {
    $client = socket_accept($socket);
    socket_getpeername($client, $address, $port);
    echo "Connection created from $address::$port.\n";
    $clientEv = new EvIo($client, Ev::READ, function($watcher) use($client) {
        /** @var EvIo $watcher */
        $watcher->stop();
        $content = '';
        while ($part = socket_read($client, 20)) {
            $content .= $part;
        }

        socket_write($client, "HTTP/1.1 200 OK\n\Hello World");
        socket_close($client);
    });

    Ev::run();
});

Ev::run();