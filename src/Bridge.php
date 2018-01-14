<?php


namespace Panlatent\Aurxy;

class Bridge
{
    protected $guzzle;

    public function __construct()
    {
        $this->guzzle = new \GuzzleHttp\Client();
    }

    /**
     * @var static
     */
    private static $bridge;

    public static function request($method, $uri, $options = [])
    {
        if (static::$bridge === null) {
            static::$bridge = new static();
        }

        return static::$bridge->guzzle->request($method, $uri, $options);
    }
}