<?php

namespace Aurxy\Event;

use Aurxy\Event;
use Aurora\Http\Transaction\Transaction;

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