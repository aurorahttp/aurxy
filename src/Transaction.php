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
     * @var Connection
     */
    protected $connection;
    /**
     * @var RequestHandlerInterface
     */
    protected $requestHandle;
    /**
     * @var ResponseHandlerInterface
     */
    protected $responseHandle;
    /**
     * @var MiddlewareInterface[]|SplPriorityQueue
     */
    protected $middlewares;
    /**
     * @var FilterInterface[]|SplPriorityQueue
     */
    protected $filters;

    /**
     * Transaction constructor.
     *
     * @param Connection               $connection
     * @param RequestHandlerInterface  $requestHandle
     * @param ResponseHandlerInterface $responseHandle
     */
    public function __construct(
        Connection $connection,
        RequestHandlerInterface $requestHandle,
        ResponseHandlerInterface $responseHandle
    ) {
        $this->connection = $connection;
        $this->requestHandle = $requestHandle;
        $this->responseHandle = $responseHandle;
        $this->middlewares = new SplPriorityQueue();
        $this->filters = new SplPriorityQueue();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response = null)
    {
        if ($response === null) {
            $response = $this->applyMiddlewares($request);
        }
        $response = $this->applyFilters($response);

        return $response;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandle(): RequestHandlerInterface
    {
        return $this->requestHandle;
    }

    /**
     * @return ResponseHandlerInterface
     */
    public function getResponseHandle(): ResponseHandlerInterface
    {
        return $this->responseHandle;
    }

    /**
     * @return MiddlewareInterface[]|SplPriorityQueue
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @return FilterInterface[]|SplPriorityQueue
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function applyMiddlewares(ServerRequestInterface $request)
    {
        foreach ($this->middlewares as $middleware) {
            $response = $middleware->process($request, $this->requestHandle);
        }

        return $response ?? $this->requestHandle->handle($request);
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function applyFilters(ResponseInterface $response)
    {
        if ($this->filters->isEmpty()) {
            $response = $this->responseHandle->handle($response);
        } else {
            foreach ($this->filters as $filter) {
                $response = $filter->process($response, $this->responseHandle);
            }
        }

        return $response;
    }
}