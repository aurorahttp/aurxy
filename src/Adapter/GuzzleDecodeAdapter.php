<?php

namespace Aurxy\Adapter;

use GuzzleHttp\Psr7\ServerRequest;
use Aurora\Http\Message\Decoder;
use Aurora\Http\Message\Decoder\Stream;

class GuzzleDecodeAdapter extends DecodeAdapter
{
    public function createServerRequest(Decoder $decoder, Stream $stream)
    {
        $request = new ServerRequest(
            $this->getMethod($stream),
            $this->getUri($stream),
            $this->getHeaders($stream)->all(),
            $this->getBody($stream),
            $this->getVersion($stream));

        return $request;
    }
}