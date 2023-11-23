<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceException;

class BillerNotSupportedException extends TransactionServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::TRANSACTION_SERVICE_BILLER_NOT_SUPPORTED;

    /**
     * BillerNotSupportedException constructor.
     * @param string $billerName Biller name
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $billerName)
    {
        parent::__construct(null, $billerName);
    }
}
