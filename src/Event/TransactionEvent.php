<?php

namespace Aurxy\Event;

use Aurxy\Event;
use Panlatent\Http\Transaction;

class TransactionEvent extends Event
{
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