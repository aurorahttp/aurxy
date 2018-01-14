<?php


namespace Panlatent\Aurxy;


class RequestFactory
{
    public $method;
    public $uri;
    public $version;
    public $headers = [];
    public $rawBody;
}