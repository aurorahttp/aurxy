<?php

namespace Panlatent\Aurxy;

use Interop\Http\Server\MiddlewareInterface;

abstract class Middleware implements MiddlewareInterface
{
    /**
     * @var Transaction
     */
    protected $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}