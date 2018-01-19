<?php

namespace Aurxy\Filter;

use GuzzleHttp\Psr7\Stream;
use Aurxy\HandleInterface;
use Aurxy\HandleReplaceInterface;
use Aurxy\HandleReplaceTrait;
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
    /**
     * @var
     */
    protected $contentTypes = [];

    public function process(ResponseInterface $response): ResponseInterface
    {
        $this->response = $response;
        if (! $this->allowContentType()) {
            return $response;
        }
        $stream = $this->createStream();

        $this->beforeProcess();
        if ($this->replaceHandle) {
            call_user_func($this->replaceHandle);
        } else {
            $this->handle();
        }
        $this->afterProcess();

        $stream->write($this->content);

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

    protected function allowContentType()
    {
        if (! $this->response->hasHeader('Content-Type')) {
            return false;
        }
        $contentType = $this->response->getHeaderLine('Content-Type');
        if (false !== ($pos = strpos($contentType, ';'))) {
            $contentType = substr($contentType, 0, $pos);
        }

        return in_array(trim($contentType), $this->contentTypes);
    }
}