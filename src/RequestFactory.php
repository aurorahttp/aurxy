<?php

namespace Panlatent\Aurxy;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Stream;
use Panlatent\Http\Message\HeaderStore;
use Panlatent\Http\Message\Request;
use Panlatent\Http\Message\ServerRequest;
use Panlatent\Http\Message\Uri;
use Panlatent\Http\Server\RequestStream;
use Panlatent\Http\Server\RequestStreamOptions;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestFactory
{
    /**
     * @var RequestStream
     */
    public $requestStream;

    /**
     * RequestFactory constructor.
     *
     * @param RequestStreamOptions $options
     */
    public function __construct(RequestStreamOptions $options)
    {
        $this->headers = new HeaderStore();
        $this->requestStream = new RequestStream($options);
    }

    /**
     * @return Request
     */
    public function createRequest()
    {
        $request = new Request($this->headers->all(), $this->getBody(), $this->getVersion(), '', $this->getMethod(),
            $this->getUri());

        return $request;
    }

    /**
     * @return ServerRequest
     */
    public function createServerRequest()
    {
        $request = new ServerRequest($this->headers->all(), $this->getBody(), $this->getVersion(), '', $this->getMethod(),
            $this->getUri());

        return $request;
    }

    /**
     * @return GuzzleRequest
     */
    public function createGuzzleRequest()
    {
        $request = new GuzzleRequest($this->getMethod(), $this->getUri(), $this->headers->all(), $this->getBody(),
            $this->getVersion());

        return $request;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->requestStream->getMethod();
    }

    /**
     * @var string
     */
    private $version;

    /**
     * @return string
     */
    public function getVersion()
    {
        if ($this->version === null) {
            $version = $this->requestStream->getVersion();
            if (false !== ($pos = strpos($version, '/'))) {
                $this->version = substr($version, $pos + 1);
            }
        }
        return $this->version;
    }

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @return UriInterface
     */
    public function getUri()
    {
        if ($this->uri === null) {
            $uri = new Uri($this->requestStream->getUri());
            if (empty($uri->getHost()) && isset($this->getHeaders()['Host'])) {
                $uri = $uri->withHost($this->getHeaders()->getLine('Host'));
            }
            $this->uri = $uri;
        }

        return $this->uri;
    }

    /**
     * @var HeaderStore
     */
    private $headers;

    /**
     * Set headers by raw content.
     *
     * @return HeaderStore
     */
    public function getHeaders()
    {
        if ($this->headers === null) {
            $this->headers = new HeaderStore();
            foreach ($this->requestStream->getStandardHeaders() as $name => $values) {
                if (in_array($name, [])) {
                    /*
                     * (!) This code block not run, because unknown cause,
                     * Guzzle will send multi same header.
                     */
                    $values = explode(',', $values);
                }
                $values = array_map(function ($value) {
                    return trim($value, "\t ");
                }, (array)$values);
                $this->headers->set($name, $values);
            }
        }

        return $this->headers;
    }

    /**
     * @var StreamInterface
     */
    private $body;

    /**
     * @return StreamInterface
     */
    public function getBody()
    {
        if ($this->body === null) {
            if ($this->requestStream->isWithBody()) {
                $stream = $this->requestStream->getBodyStream();
            } else {
                $stream = fopen('php://memory', 'r+');
            }
            $this->body = new Stream($stream);
        }

        return $this->body;
    }
}