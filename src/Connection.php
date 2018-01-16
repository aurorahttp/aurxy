<?php

namespace Panlatent\Aurxy;

use Aurxy;
use Panlatent\Aurxy\Ev\SafeCallback;
use Panlatent\Aurxy\Event\TransactionEvent;
use Panlatent\Aurxy\Middleware\GuzzleBridgeMiddleware;
use Panlatent\Http\Exception\Client\LengthRequiredException;
use Panlatent\Http\RawMessage\RawRequestOptions;
use Psr\Http\Message\ResponseInterface;

class Connection
{
    const EVENT_TRANSACTION_BEFORE = 'connection.transaction::before';
    const EVENT_TRANSACTION_HANDLE_BEFORE = 'connection.transaction_handle::before';
    const EVENT_TRANSACTION_HANDLE_AFTER = 'connection.transaction_handle::after';

    /**
     * @var resource client socket resource.
     */
    protected $socket;
    /**
     * @var \EvTimer
     */
    protected $waitTimeoutEvent;
    /**
     * @var \EvIo
     */
    protected $socketReadEvent;
    /**
     * @var \EvIo
     */
    protected $socketWriteEvent;
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * Connection constructor.
     *
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
        socket_set_nonblock($socket);
        socket_getpeername($socket, $address, $port);
        echo "\nConnection created from $address::$port :\n";
    }

    /**
     * Init status and register watchers.
     */
    public function handle()
    {
        $this->socketReadEvent = new \EvIo($this->socket, \Ev::READ, new SafeCallback(function () {
            $this->onRead();
        }));
    }

    /**
     * Stop all watchers and close client socket.
     */
    public function close()
    {
        $this->socketReadEvent and $this->socketReadEvent->stop();
        $this->socketWriteEvent and $this->socketWriteEvent->stop();
        $this->waitTimeoutEvent and $this->waitTimeoutEvent->stop();

        socket_close($this->socket);
        echo "=> Clone connection\n";
    }

    /**
     * Read socket and write buffer.
     */
    public function onRead()
    {
        if ($this->requestFactory === null) {
            $options = new RawRequestOptions();
            $options->headerReadyEvent = [$this, 'afterRequestHeader'];
            $options->bodyReadyEvent = [$this, 'afterRequestBody'];
            $this->requestFactory = new RequestFactory($options);
        }

        $part = socket_read($this->socket, 1024);
        $length = 0;
        for (; $length != strlen($part);) {
            $length += $successLength = $this->requestFactory->rawRequestStream->write(substr($part, $length));
        }
    }


    /**
     * Event
     *
     * @throws LengthRequiredException
     */
    public function afterRequestHeader()
    {
        $uri = $this->requestFactory->getUri();
        echo "=> Request: {$this->requestFactory->getMethod()} {$uri} [{$this->requestFactory->getVersion()}] -> ";

        if ($this->requestFactory->getMethod() == 'CONNECT') {
            echo ' Create Tunnel -> ';
            // $tunnel = new Tunnel($uri->getHost(), $uri->getPort());
            $this->socketReadEvent->stop();
            socket_close($this->socket);
            echo " No Support\n";

            return;
        } elseif ($this->requestFactory->getMethod() == 'POST') {
            if (! $this->requestFactory->getHeaders()->has('Content-Length')) {
                throw new LengthRequiredException();
            }
            /*
             * If Content-Length is zero, can't read socket.
             */
            if ($this->requestFactory->getHeaders()->getLine('Content-Length') == 0) {
                $this->transaction();
            }

            return;
        }
        $this->transaction();
    }

    /**
     * Event
     */
    public function afterRequestBody()
    {
        $this->transaction();
    }

    /**
     * Run a HTTP transaction.
     */
    public function transaction()
    {
        Aurxy::event(static::EVENT_TRANSACTION_BEFORE);
        $transaction = new Transaction($this, new RequestHandler(),
            [new GuzzleBridgeMiddleware()],
            [new ResponseFixed()]);
        $request = $this->requestFactory->createServerRequest();
        $event = new TransactionEvent($transaction);
        Aurxy::event(static::EVENT_TRANSACTION_HANDLE_BEFORE, $event);
        $response = $transaction->handle($request);
        Aurxy::event(static::EVENT_TRANSACTION_HANDLE_AFTER, $event);
        $this->sendResponse($response);
    }

    /**
     * Send a response to client socket.
     *
     * @param ResponseInterface $response
     */
    public function sendResponse(ResponseInterface $response)
    {
        $buffer = 'HTTP/' . implode(' ', [
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ]) . "\r\n";
        foreach (array_keys($response->getHeaders()) as $key) {
            $buffer .= $key . ': ' . $response->getHeaderLine($key) . "\r\n";
        }

        $buffer .= "\r\n";
        $buffer .= $response->getBody()->getContents();

        $this->socketReadEvent->stop();
        $this->socketWriteEvent = new \EvIo($this->socket, \Ev::WRITE, function () use (&$buffer) {
            echo '=> Write => size ', strlen($buffer), " bytes\n";
            $length = @socket_write($this->socket, $buffer);
            if ($length === false) {
                echo '=> socket error: ' . socket_last_error($this->socket) . "\n";

                return;
            }
            $buffer = substr($buffer, $length);
            if (empty($buffer)) {
                $this->close();
            }
        });
    }
}