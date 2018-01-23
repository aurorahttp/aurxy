<?php

namespace Aurxy\Adapter;

use Aurora\Http\Message\Encoder;
use Aurora\Http\Message\Encoder\AdapterInterface;
use Aurora\Http\Message\Encoder\Stream;
use Psr\Http\Message\ResponseInterface;

class EncodeAdapter implements AdapterInterface
{
    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function createStream(Encoder $encoder, ResponseInterface $response): Stream
    {
        $stream = new Stream();
//        $stream->getContext()->setBufferFlushReady(function() use($stream) {
//        });

        $stream->writeln(implode(' ',
            [
                'HTTP/' . $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ]));

        foreach (array_keys($response->getHeaders()) as $key) {
            $stream->writeln($key . ': ' . $response->getHeaderLine($key) . "\r\n");
        }
        $stream->writeln();
        $stream->withBodyStream($response->getBody());

        return $stream;
    }
}