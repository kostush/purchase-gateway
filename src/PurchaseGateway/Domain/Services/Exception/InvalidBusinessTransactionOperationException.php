<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class InvalidBusinessTransactionOperationException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_BUSINESS_TRANSACTION_OPERATION_EXCEPTION;

    /**
     * @param string $businessTransactionOperation Force cascade.
     * @param \Throwable|null $previous Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $businessTransactionOperation, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $businessTransactionOperation);
    }
}
