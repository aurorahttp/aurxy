<?php

namespace Panlatent\Aurxy\Event;

use Panlatent\Aurxy\Event;
use Panlatent\Aurxy\Transaction;

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