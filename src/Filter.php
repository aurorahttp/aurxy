<?php

namespace Panlatent\Aurxy;

use Panlatent\Http\Server\FilterInterface;

abstract class Filter implements FilterInterface
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