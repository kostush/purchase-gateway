<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceException;

class UnableToProcessTransactionException extends TransactionServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::UNABLE_TO_PROCESS_TRANSACTION_EXCEPTION;
}
