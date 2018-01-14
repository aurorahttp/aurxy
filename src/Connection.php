<?php

namespace Panlatent\Aurxy;

use Panlatent\Http\Exception\Client\LengthRequiredException;
use Panlatent\Http\Message\Uri;

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

    protected $message;

    protected $messageStatus;

    protected $connectionTimeoutEvent;

    protected $socketReadEvent;

    protected $socketWriteEvent;

    /**
     * @var RequestFactory
     */
    protected $request;

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
        $this->socketReadEvent = new \EvIo($this->socket, \Ev::READ, [$this, 'onRead']);
    }

    public function onRead(\EvIo $watcher)
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
                $this->buffer = substr($part, $pos + 2);

                return;
            }
        } elseif ($this->messageStatus == static::MSG_BODY_DOING) {
            $length = $this->request->headers['Content-Length'];
            if ($length == strlen($this->buffer) + strlen($part)) {
                $this->buffer .= $part;
                $this->bodyReady();
                $this->messageStatus = static::MSG_BODY_DONE;

                return;
            }

        }
        $this->buffer .= $part;
    }

    public function firstLineReady()
    {
        $this->request = new RequestFactory();
        list($this->request->method, $this->request->uri, $this->request->version) = explode(' ', $this->buffer);
        $this->buffer = '';
    }

    public function headReady()
    {
        $rawHeader = $this->buffer;
        $rawHeaderLines = explode("\r\n", $rawHeader);
        foreach ($rawHeaderLines as $headerRow) {
            list($field, $value) = explode(":", $headerRow);
            $this->request->headers[$field] = $value;
        }

        $uri = new Uri($this->request->uri);
        if (empty($uri->getHost()) && isset($this->request->headers['Host'])) {
            $uri = $uri->withHost($this->request->headers['Host']);
        }

        echo "=> Request: {$this->request->method} {$uri} [{$this->request->version}] -> ";
        if ($this->request->method == 'CONNECT') {
            echo ' Create Tunnel -> ';
            $tunnel = new Tunnel($uri->getHost(), $uri->getPort());
            $this->socketReadEvent->stop();
            socket_close($this->socket);
            echo " No Support\n";

            return;
        } elseif ($this->request->method == 'POST') {
            if (! isset($this->request->headers['Content-Length'])) {
                throw new LengthRequiredException();
            }
            $this->messageStatus = static::MSG_BODY_WAITING;
            /*
             * If Content-Length is zero, can't read socket.
             */
            if ($this->request->headers['Content-Length'] == 0) {
                $this->messageStatus = static::MSG_BODY_DONE;
                $this->transmit();
            }

            return;
        }

        $this->transmit();
    }

    public function bodyReady()
    {
        $this->request->rawBody = $this->buffer;
        $this->buffer = '';
        $this->transmit();
    }

    public function transmit()
    {
        $uri = new Uri($this->request->uri);
        if (empty($uri->getHost()) && isset($this->request->headers['Host'])) {
            $uri = $uri->withHost($this->request->headers['Host']);
        }

        $headers = $this->request->headers;
        if (isset($headers['Connection'])) {
            $headers['Connection'] = 'close';
        }

        $options = [
            'timeout'         => 3,
            'connect_timeout' => 2,
//            'debug'  =>  true,
            'headers'         => $headers,
        ];
        if ($this->request->method == 'POST') {
            $options['body'] = $this->request->rawBody;
        }

        $response = Bridge::request($this->request->method, $uri, $options);
        echo "done {$response->getBody()->getSize()} byte.\n";

        $buffer = 'HTTP/' . implode(' ', [
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ]) . "\r\n";
        foreach (array_keys($response->getHeaders()) as $key) {
            if (in_array($key, ['Transfer-Encoding'])) {
                /*
                 * Transfer-Encoding header need set a size to body first line.
                 */
                echo "=> Skip $key: {$response->getHeaderLine($key)}\n";
                continue;
            }
            $buffer .= $key . ': ' . $response->getHeaderLine($key) . "\r\n";
        }

        $buffer .= "\r\n";
        $buffer .= $response->getBody()->getContents();

        $this->socketReadEvent->stop();

        $this->socketWriteEvent = new \EvIo($this->socket, \Ev::WRITE, function ($watcher) use (&$buffer) {
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