<?php

namespace Panlatent\Aurxy\Filter;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

class ContentFilter extends ResponseFilter
{
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var string
     */
    protected $content;

    public function process(ResponseInterface $response): ResponseInterface
    {
        $this->response = $response;
        $stream = $this->createStream();
        $this->beforeProcess();
        $stream->write($this->handle());
        $this->afterProcess();

        return $response->withBody($stream);
    }

    public function handle()
    {

    }

    protected function createStream()
    {
        $stream = fopen('php://temp', 'r+');

        return new Stream($stream);
    }

    protected function beforeProcess()
    {
        $this->content = $this->response->getBody()->getContents();
    }

    protected function afterProcess()
    {

    }
}