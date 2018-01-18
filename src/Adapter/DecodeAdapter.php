<?php

namespace Panlatent\Aurxy\Adapter;

use Panlatent\Http\Message\Decoder;
use Panlatent\Http\Message\Decoder\AdapterInterface;
use Panlatent\Http\Message\Decoder\Stream;
use Panlatent\Http\Message\HeaderStore;
use Panlatent\Http\Message\ServerRequest;
use Panlatent\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

abstract class DecodeAdapter implements AdapterInterface
{
    /**
     * @param Decoder $decoder
     * @param Stream  $stream
     * @return ServerRequest|ServerRequestInterface
     */
    public function createServerRequest(Decoder $decoder, Stream $stream)
    {
        $request = new ServerRequest(
            $this->headers->all(),
            $this->getBody($stream),
            $this->getVersion($stream), '',
            $this->getMethod($stream),
            $this->getUri($stream));

        return $request;
    }

    /**
     * @param Stream $stream
     * @return string
     */
    public function getMethod(Stream $stream)
    {
        return $stream->getMethod();
    }

    /**
     * @var string
     */
    private $version;

    /**
     * @param Stream $stream
     * @return string
     */
    public function getVersion(Stream $stream)
    {
        if ($this->version === null) {
            $version = $stream->getVersion();
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
     * @param Stream $stream
     * @return UriInterface
     */
    public function getUri(Stream $stream)
    {
        if ($this->uri === null) {
            $uri = new Uri($stream->getUri());
            if (empty($uri->getHost()) && isset($this->getHeaders($stream)['Host'])) {
                $uri = $uri->withHost($this->getHeaders($stream)->getLine('Host'));
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
     * @param Stream $stream
     * @return HeaderStore
     */
    public function getHeaders(Stream $stream)
    {
        if ($this->headers === null) {
            $this->headers = new HeaderStore();
            foreach ($stream->getStandardHeaders() as $name => $values) {
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
     * @param Stream $stream
     * @return StreamInterface
     */
    public function getBody(Stream $stream)
    {
        if ($this->body === null) {
            if ($stream->isWithBody()) {
                $stream = $stream->getBodyStream();
            } else {
                $stream = fopen('php://memory', 'r+');
            }
            $this->body = new \GuzzleHttp\Psr7\Stream($stream); // (!)
        }

        return $this->body;
    }
}