<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class TransactionNotFoundException extends Exception
{
    protected $code = Code::TRANSACTION_NOT_FOUND_EXCEPTION;

    /**
     * TransactionDataNotFoundException constructor.
     *
     * @param string          $transactionId Transaction id
     * @param \Throwable|null $previous      Previous exception
     * @throws LoggerException
     */
    public function __construct(string $transactionId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $transactionId);
    }
}
