<?php

namespace Panlatent\Aurxy;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Stream;
use Panlatent\Http\Message\HeaderStore;
use Panlatent\Http\Message\Request;
use Panlatent\Http\Message\ServerRequest;
use Panlatent\Http\Message\Uri;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestFactory
{
    /**
     * @var HeaderStore
     */
    public $headers;

    /**
     * RequestFactory constructor.
     *
     * @param string $method
     * @param string $uri
     * @param string $version
     */
    public function __construct($method = null, $uri = null, $version = '1.1')
    {
        $this->headers = new HeaderStore();
        $this->setMethod($method);
        $this->setRawUri($uri);
        $this->setVersion($version);
    }

    /**
     * @return Request
     */
    public function createRequest()
    {
        $request = new Request($this->headers->all(), $this->getBody(), $this->getVersion(), '', $this->method,
            $this->getUri());

        return $request;
    }

    /**
     * @return ServerRequest
     */
    public function createServerRequest()
    {
        $request = new ServerRequest($this->headers->all(), $this->getBody(), $this->getVersion(), '', $this->method,
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
     * @var string
     */
    private $method;

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
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
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        if (false !== ($pos = strpos($version, '/'))) {
            $version = substr($version, $pos + 1);
        }
        $this->version = $version;
    }

    /**
     * @var string
     */
    private $rawUri;

    /**
     * @return string
     */
    public function getRawUri()
    {
        return $this->rawUri;
    }

    /**
     * @param string $rawUri
     */
    public function setRawUri(string $rawUri)
    {
        $this->rawUri = $rawUri;
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
            $uri = new Uri($this->rawUri);
            if (empty($uri->getHost()) && isset($this->headers['Host'])) {
                $uri = $uri->withHost($this->headers->getLine('Host'));
            }
            $this->uri = $uri;
        }

        return $this->uri;
    }

    /**
     * Set headers by raw content.
     *
     * @param string $rawContent
     */
    public function setHeadersByRawContent($rawContent)
    {
        $rawHeaderLines = explode("\r\n", $rawContent);
        foreach ($rawHeaderLines as $headerRow) {
            list($name, $values) = explode(":", $headerRow);
            if (in_array($name, [])) {
                /*
                 * (!) This code block not run, because unknown cause,
                 * Guzzle will send multi same header.
                 */
                $values = explode(',', $values);
            }
            $values = array_map(function($value) {
                return trim($value, "\t ");
            }, (array)$values);
            $this->headers->set($name, $values);
        }
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
            $stream = fopen('php://temp', 'r+');
            $this->body = new Stream($stream);
        }

        return $this->body;
    }
}