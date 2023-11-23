<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\Logger\Exception as LoggerException;

/**
 * Class TransactionDataNotFoundException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class TransactionDataNotFoundException extends RetrieveTransactionDataException
{
    protected $code = Code::TRANSACTION_DATA_NOT_FOUND_EXCEPTION;

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
