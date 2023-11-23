<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceException;

/**
 * Class RetrieveTransactionDataException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class AbortTransactionException extends TransactionServiceException
{
    protected $code = Code::ABORT_TRANSACTION_EXCEPTION;
}
