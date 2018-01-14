<?php

namespace Panlatent\Aurxy;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;

/**
 * Class Bridge
 *
 * @author Panlatent <panlatent@gmail.com>
 */
class Bridge
{
    /**
     * @var Client
     */
    protected $guzzle;

    /**
     * Bridge constructor.
     */
    public function __construct()
    {
        $this->guzzle = new Client();
    }

    /**
     * @var static
     */
    private static $bridge;

    /**
     * Send request via Guzzle by params.
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public static function request($method, $uri, $options = [])
    {
        if (static::$bridge === null) {
            static::$bridge = new static();
        }

        return static::$bridge->guzzle->request($method, $uri, $options);
    }

    /**
     * Send request via Guzzle by PSR-7 Request object.
     *
     * @param RequestInterface $request
     * @param array            $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public static function send(RequestInterface $request, array $options = [])
    {
        if (static::$bridge === null) {
            static::$bridge = new static();
        }

        return static::$bridge->guzzle->send($request, $options);
    }
}