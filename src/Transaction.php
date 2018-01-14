<?php

namespace Panlatent\Aurxy;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Panlatent\Aurxy\Filter\RequestFilter;
use Panlatent\Aurxy\Filter\ResponseFilter;
use Panlatent\Http\Server\FilterInterface;
use Panlatent\Http\Server\RequestFilterInterface;
use Panlatent\Http\Server\ResponseFilterInterface;
use Psr\Http\Message\RequestInterface;
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
     * @var MiddlewareInterface[]|SplPriorityQueue
     */
    protected $middlewares;
    /**
     * @var RequestFilterInterface[]|SplPriorityQueue
     */
    protected $requestFilters;
    /**
     * @var ResponseFilterInterface[]|SplPriorityQueue
     */
    protected $responseFilters;

    /**
     * Transaction constructor.
     *
     * @param Connection               $connection
     * @param RequestHandlerInterface  $requestHandle
     * @param array                    $middlewares
     * @param array                    $filters
     */
    public function __construct(
        Connection $connection,
        RequestHandlerInterface $requestHandle,
        array $middlewares = [],
        array $filters = []
    ) {
        $this->connection = $connection;
        $this->requestHandle = $requestHandle;
        $this->middlewares = new SplPriorityQueue();
        $this->requestFilters = new SplPriorityQueue();
        $this->responseFilters = new SplPriorityQueue();
        $this->setMiddlewares($middlewares);
        $this->setFilters($filters);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request)
    {
        $request = $this->applyRequestFilters($request);
        $response = $this->applyMiddlewares($request);
        $response = $this->applyResponseFilters($response);

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
     * @return MiddlewareInterface[]|SplPriorityQueue
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Set middlewares with priority.
     * [
     *     [1, $middleware],
     * ]
     *
     * @param Middleware[] $middlewares
     */
    public function setMiddlewares($middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->middlewares->insert($middleware, $middleware->getPriority());
        }
    }

    /**
     * @return RequestFilterInterface[]|SplPriorityQueue
     */
    public function getRequestFilters()
    {
        return $this->requestFilters;
    }

    /**
     * @param RequestFilter[] $filters
     */
    public function setRequestFilters($filters)
    {
        foreach ($filters as $filter) {
            $this->requestFilters->insert($filter, $filter->getPriority());
        }
    }

    /**
     * @return ResponseFilterInterface[]|SplPriorityQueue
     */
    public function getResponseFilters()
    {
        return $this->responseFilters;
    }

    /**
     * @param ResponseFilter[] $filters
     */
    public function setResponseFilters($filters)
    {
        foreach ($filters as $filter) {
            $this->requestFilters->insert($filter, $filter->getPriority());
        }
    }

    /**
     * Set filters with priority.
     *
     * @param FilterInterface[] $filters
     */
    public function setFilters($filters)
    {
        foreach ($filters as $filter) {
            if ($filter instanceof PriorityInterface) {
                $priority = $filter->getPriority();
            } else {
                $priority = 1;
            }
            if ($filter instanceof RequestFilterInterface) {
                $this->requestFilters->insert($filter, $priority);
            } elseif ($filter instanceof ResponseFilterInterface) {
                $this->responseFilters->insert($filter, $priority);
            }
        }
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
     * @param ServerRequestInterface $request
     * @return RequestInterface|ServerRequestInterface
     */
    protected function applyRequestFilters(ServerRequestInterface $request)
    {
        foreach ($this->requestFilters as $filter) {
            $request = $filter->process($request);
        }

        return $request;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function applyResponseFilters(ResponseInterface $response)
    {
        foreach ($this->responseFilters as $filter) {
            $response = $filter->process($response);
        }

        return $response;
    }
}