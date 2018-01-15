<?php

namespace Panlatent\Aurxy\Filter;

use GuzzleHttp\Psr7\Stream;
use Panlatent\Aurxy\HandleInterface;
use Panlatent\Aurxy\HandleReplaceInterface;
use Panlatent\Aurxy\HandleReplaceTrait;
use Psr\Http\Message\ResponseInterface;

abstract class ContentFilter extends ResponseFilter implements HandleInterface, HandleReplaceInterface
{
    use HandleReplaceTrait;
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

        if ($this->replaceHandle) {
            call_user_func($this->replaceHandle);
        } else {
            $this->handle();
        }

        $stream->write($this->content);
        $this->afterProcess();

        return $response->withBody($stream);
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