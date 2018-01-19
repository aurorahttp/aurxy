<?php

namespace Aurxy\Transaction;

interface TransactionControlInterface
{
    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction;
}