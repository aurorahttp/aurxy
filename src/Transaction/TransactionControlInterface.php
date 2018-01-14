<?php


namespace Panlatent\Aurxy\Transaction;

use Panlatent\Aurxy\Transaction;

interface TransactionControlInterface
{
    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction;
}