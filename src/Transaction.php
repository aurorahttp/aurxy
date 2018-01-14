<?php


namespace Panlatent\Aurxy;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\ResponseHandlerInterface;
use Panlatent\Http\Server\FilterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SplPriorityQueue;

class Transaction
{
    /**
     * @var RequestHandlerInterface
     */
    protected $requestHandle;
    /**
     * @var ResponseHandlerInterface
     */
    protected $responseHandle;
    /**
     * @var MiddlewareInterface[]
     */
    protected $middlewares;
    /**
     * @var FilterInterface[]
     */
    protected $filters;

    /**
     * Transaction constructor.
     *
     * @param RequestHandlerInterface  $requestHandle
     * @param ResponseHandlerInterface $responseHandle
     */
    public function __construct(RequestHandlerInterface $requestHandle, ResponseHandlerInterface $responseHandle)
    {
        $this->requestHandle = $requestHandle;
        $this->responseHandle = $responseHandle;
        $this->middlewares = new SplPriorityQueue();
        $this->filters = new SplPriorityQueue();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request)
    {
        $response = $this->requestHandle->handle($request);
        foreach ($this->middlewares as $middleware) {
            $response = $middleware->process($request, $this->requestHandle);
        }

        foreach ($this->filters as $filter) {
            $response = $filter->process($response, $this->responseHandle);
        }

        return $this->responseHandle->handle($response);
    }
}