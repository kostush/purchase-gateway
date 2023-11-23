<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceException;

/**
 * Class RetrieveTransactionDataException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class RetrieveTransactionDataException extends TransactionServiceException
{
    protected $code = Code::RETRIEVE_TRANSACTION_DATA_EXCEPTION;
}
