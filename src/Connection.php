<?php

namespace Aurxy;

use Aurxy;
use Aurxy\Adapter\GuzzleDecodeAdapter;
use Aurxy\Ev\SafeCallback;
use Aurxy\Event\TransactionEvent;
use Aurxy\Filter\ResponseFixed;
use Aurxy\Middleware\GuzzleBridgeMiddleware;
use Ev;
use Panlatent\Http\Client\LengthRequiredException;
use Panlatent\Http\Message\Decoder;
use Panlatent\Http\Message\Decoder\Stream;
use Panlatent\Http\Message\Encoder;
use Panlatent\Http\Transaction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var Stream
     */
    protected $decodeStream;

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
        Aurxy::debug("connection created from $address:$port");
    }

    /**
     * Init status and register watchers.
     */
    public function handle()
    {
        $this->socketReadEvent = new \EvIo($this->socket, Ev::READ, new SafeCallback(function () {
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

        socket_getpeername($this->socket, $address, $port);
        socket_close($this->socket);
        Aurxy::debug("connection close from $address:$port");
    }

    /**
     * Read socket and write buffer.
     */
    public function onRead()
    {
        if ($this->decodeStream === null) {
            $this->decodeStream = new Stream();
            $this->decodeStream->getContext()->setHeaderReady([$this, 'afterRequestHeader']);
            $this->decodeStream->getContext()->setBodyReady([$this, 'afterRequestBody']);
        }

        $part = socket_read($this->socket, 1024);
        $length = 0;
        for (; $length != strlen($part);) {
            $length += $successLength = $this->decodeStream->write(substr($part, $length));
        }
    }

    /**
     * Event
     *
     * @throws LengthRequiredException
     */
    public function afterRequestHeader()
    {
        $codec = new Decoder();
        $codec->setAdapter(new GuzzleDecodeAdapter());
        $request = $codec->decode($this->decodeStream);

        $uri = $this->decodeStream->getUri();
        Aurxy::access("{$request->getMethod()} {$uri} HTTP/{$request->getProtocolVersion()}");

        if ($request->getMethod() == 'CONNECT') {
            Aurxy::debug('not supported client create tunnel');
            // $tunnel = new Tunnel($uri->getHost(), $uri->getPort());
            $this->socketReadEvent->stop();
            $this->close();

            return;
        } elseif ($request->getMethod() == 'POST') {
            if (! $request->hasHeader('Content-Length')) {
                throw new LengthRequiredException();
            }
            /*
             * If Content-Length is zero, can't read socket.
             */
            if ($request->getHeaderLine('Content-Length') == 0) {
                $this->transaction($request);
            }

            return;
        }
        $this->transaction($request);
    }

    /**
     * Event
     */
    public function afterRequestBody()
    {
        // empty
    }

    /**
     * Run a HTTP transaction.
     *
     * @param ServerRequestInterface $request
     */
    public function transaction(ServerRequestInterface $request)
    {
        Aurxy::event(static::EVENT_TRANSACTION_BEFORE);
        $transaction = new Transaction();
        $transaction->getMiddlewares()->push(new GuzzleBridgeMiddleware());
        $transaction->getFilters()->insert(new ResponseFixed(), 0);
        $transaction->setRequestHandler(new RequestHandler());
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
        $this->socketReadEvent->stop();
        $encoder = new Encoder();
        $stream = $encoder->encode($response);
        $buffer = $stream->getContents();
        $this->socketWriteEvent = new \EvIo($this->socket, Ev::WRITE, function () use (&$buffer) {
            Aurxy::debug('write => size ' . strlen($buffer) . " bytes");
            $length = @socket_write($this->socket, $buffer);
            if ($length === false) {
                Aurxy::error('socket error: ' . socket_last_error($this->socket));

                return;
            }
            $buffer = substr($buffer, $length);
            if (empty($buffer)) {
                $this->close();
            }
        });
    }
}