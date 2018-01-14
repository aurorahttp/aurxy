<?php

namespace Panlatent\Aurxy;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;
use Panlatent\Aurxy\Ev\SafeCallback;
use Panlatent\Http\Exception\Client\LengthRequiredException;
use Psr\Http\Message\ResponseInterface;

class Connection
{
    const MSG_FIRST_LINE_WAITING = 0;
    const MSG_HEAD_WAITING = 10;
    const MSG_HEAD_DOING = 11;
    const MSG_HEAD_DONE = 19;
    const MSG_BODY_WAITING = 20;
    const MSG_BODY_DOING = 21;
    const MSG_BODY_DONE = 29;

    protected $socket;

    protected $buffer;

    protected $writeBodyLength;

    protected $message;

    protected $messageStatus;
    /**
     * @var \EvTimer
     */
    protected $connectionTimeoutEvent;
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

    public function __construct($socket)
    {
        $this->socket = $socket;
        socket_set_nonblock($socket);
        socket_getpeername($socket, $address, $port);
        echo "\nConnection created from $address::$port :\n";
    }

    public function handle()
    {
        $this->messageStatus = static::MSG_FIRST_LINE_WAITING;
        $this->socketReadEvent = new \EvIo($this->socket, \Ev::READ, new SafeCallback(function () {
            $this->onRead();
        }));
    }

    public function close()
    {

    }

    public function onRead()
    {
        if ($this->messageStatus == static::MSG_HEAD_WAITING) {
            $this->messageStatus = static::MSG_HEAD_DOING;
        } elseif ($this->messageStatus == static::MSG_BODY_WAITING) {
            $this->messageStatus = static::MSG_BODY_DOING;
        }

        $part = socket_read($this->socket, 1024);
        if ($this->messageStatus == static::MSG_FIRST_LINE_WAITING) {
            if (false === ($pos = strpos($part, "\r\n"))) {
                $this->buffer .= $part;

                return;
            }
            $this->buffer .= substr($part, 0, $pos);
            $this->firstLineReady();
            $part = substr($part, $pos + 2);
            $this->messageStatus = static::MSG_HEAD_DOING;
        }
        if ($this->messageStatus == static::MSG_HEAD_DOING) {
            if (false !== ($pos = strpos($part, "\r\n\r\n"))) {
                $this->buffer .= substr($part, 0, $pos);
                $this->messageStatus = static::MSG_HEAD_DONE;
                $this->headReady();
                $this->buffer = substr($part, $pos + 4);

                return;
            }
        } elseif ($this->messageStatus == static::MSG_BODY_DOING) {
            if ($this->requestFactory->getBody()->isWritable()) {
                if ($this->requestFactory->getBody()->write($this->buffer . $part)) {
                    $this->writeBodyLength += strlen($this->buffer) + strlen($part);
                    $this->buffer = '';
                    $length = $this->requestFactory->headers['Content-Length'];
                    if ($length <= $this->writeBodyLength) {
                        $this->bodyReady();
                        $this->messageStatus = static::MSG_BODY_DONE;
                    }

                    return;
                }
            }
        }
        $this->buffer .= $part;
    }

    public function firstLineReady()
    {
        list($method, $uri, $version) = explode(' ', $this->buffer);
        $this->requestFactory = new RequestFactory($method, $uri, $version);
        $this->buffer = '';
    }

    public function headReady()
    {
        $this->requestFactory->setHeadersByRawContent($this->buffer);
        $uri = $this->requestFactory->getUri();
        echo "=> Request: {$this->requestFactory->getMethod()} {$uri} [{$this->requestFactory->getVersion()}] -> ";

        if ($this->requestFactory->getMethod() == 'CONNECT') {
            echo ' Create Tunnel -> ';
//            $tunnel = new Tunnel($uri->getHost(), $uri->getPort());
            $this->socketReadEvent->stop();
            socket_close($this->socket);
            echo " No Support\n";

            return;
        } elseif ($this->requestFactory->getMethod() == 'POST') {
            if (! $this->requestFactory->headers->has('Content-Length')) {
                throw new LengthRequiredException();
            }
            $this->messageStatus = static::MSG_BODY_WAITING;
            /*
             * If Content-Length is zero, can't read socket.
             */
            if ($this->requestFactory->headers->getLine('Content-Length') == 0) {
                $this->messageStatus = static::MSG_BODY_DONE;
                $this->transmit();
            }

            return;
        }

        $this->transmit();
    }

    public function bodyReady()
    {
        $this->transmit();
    }

    public function transmit()
    {
        $request = $this->requestFactory->createServerRequest();
        $options = [
            'timeout'         => 5.0,
            'connect_timeout' => 5.0,
            'headers'         => $request->getHeaders(),
        ];

        try {
            $response = Bridge::send($request, $options);
            echo "done {$response->getBody()->getSize()} byte.\n";
        } catch (BadResponseException $exception) {
            $response = $exception->getResponse();
        } catch (TransferException $exception) {
            echo "failed {$exception->getMessage()}\n";
            $response = (new BadGatewayResponseFactory($request, $exception))->createResponse();
        }
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
            if (in_array($key, ['Transfer-Encoding'])) {
                /*
                 * Transfer-Encoding header need set a size to body first line.
                 * Now working method not support Transfer-Encoding header.
                 */
                echo "=> Skip $key: {$response->getHeaderLine($key)}\n";
                continue;
            }
            $buffer .= $key . ': ' . $response->getHeaderLine($key) . "\r\n";
        }

        $buffer .= "\r\n";
        $buffer .= $response->getBody()->getContents();

        $this->socketReadEvent->stop();
        $this->socketWriteEvent = new \EvIo($this->socket, \Ev::WRITE, function (\EvIo $watcher) use (&$buffer) {
            echo '=> Write => size ', strlen($buffer), " bytes\n";
            $length = socket_write($this->socket, $buffer);
            if ($length === false) {
                echo socket_last_error($this->socket), ',';
            }
            $buffer = substr($buffer, $length);
            if (empty($buffer)) {
                $watcher->stop();
                socket_close($this->socket);
                echo "=> Clone connection\n";
            }
        });
    }
}