<?php

namespace Aurxy;

use Aurora\Http\Connection\ClientConnection;
use Aurxy;
use Aurxy\Adapter\GuzzleDecodeAdapter;
use Aurxy\Ev\SafeCallback;
use Aurxy\Event\TransactionEvent;
use Aurxy\Filter\ResponseFixed;
use Aurxy\Middleware\GuzzleBridgeMiddleware;
use Ev;
use Aurora\Http\Client\LengthRequiredException;
use Aurora\Http\Message\Decoder;
use Aurora\Http\Message\Decoder\Stream;
use Aurora\Http\Message\Encoder;
use Aurora\Http\Transaction\Transaction;
use EvIo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Session
{
    const EVENT_TRANSACTION_BEFORE = 'connection.transaction::before';
    const EVENT_TRANSACTION_HANDLE_BEFORE = 'connection.transaction_handle::before';
    const EVENT_TRANSACTION_HANDLE_AFTER = 'connection.transaction_handle::after';

    /**
     * @var ClientConnection
     */
    protected $connection;
    /**
     * @var \EvTimer
     */
    protected $waitTimeoutEvent;
    /**
     * @var EvIo
     */
    protected $socketReadEvent;
    /**
     * @var EvIo
     */
    protected $socketWriteEvent;
    /**
     * @var Stream
     */
    protected $decodeStream;

    /**
     * Connection constructor.
     *
     * @param ClientConnection $connection
     */
    public function __construct(ClientConnection $connection)
    {
        $this->connection = $connection;
        stream_set_blocking($connection->getSocket(), 0);
        Aurxy::debug("connection created from {$connection->getAddress()}:{$connection->getPort()}");

        $this->socketReadEvent = new EvIo($connection->getSocket(), Ev::READ, new SafeCallback(function () {
            $this->onRead();
        }));

        $this->decodeStream = new Stream();
        $this->decodeStream->getContext()->setHeaderReady([$this, 'afterRequestHeader']);
        $this->decodeStream->getContext()->setBodyReady([$this, 'afterRequestBody']);
    }

    /**
     * Stop all watchers and close client socket.
     */
    public function close()
    {
        $this->socketReadEvent and $this->socketReadEvent->stop();
        $this->socketWriteEvent and $this->socketWriteEvent->stop();
        $this->waitTimeoutEvent and $this->waitTimeoutEvent->stop();
        $this->connection->close();
        Aurxy::debug("connection close from {$this->connection->getAddress()}:{$this->connection->getPort()}");
    }

    /**
     * Read socket and write buffer.
     */
    public function onRead()
    {
        $part = $this->connection->read(1024);
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
        $transaction = new Transaction($request);
        $transaction->getMiddlewares()->push(new GuzzleBridgeMiddleware());
        $transaction->getFilters()->insert(new ResponseFixed(), 0);
        $transaction->setRequestHandler(new RequestHandler());
        $event = new TransactionEvent($transaction);
        Aurxy::event(static::EVENT_TRANSACTION_HANDLE_BEFORE, $event);
        $response = $transaction->handle();
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
        $this->socketWriteEvent = new EvIo($this->connection->getSocket(), Ev::WRITE, function () use (&$buffer) {
            echo $length = $this->connection->write($buffer, strlen($buffer));
            Aurxy::debug('write => size ' . $length . " bytes");
            if ($length === false) {
                Aurxy::error('socket error');

                return;
            }
            $buffer = substr($buffer, $length);
            if (empty($buffer)) {
                $this->close();
            }
        });
    }
}