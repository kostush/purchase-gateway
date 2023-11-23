<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\Logger\Exception as LoggerException;

/**
 * Class InvalidTransactionDataResponseException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class InvalidTransactionDataResponseException extends RetrieveTransactionDataException
{
    protected $code = Code::INVALID_TRANSACTION_DATA_RESPONSE_EXCEPTION;

    /**
     * InvalidTransactionDataResponseException constructor.
     *
     * @param \Throwable|null $previous Previous exception
     * @throws LoggerException
     */
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct($previous);
    }
}
