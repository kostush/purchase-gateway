<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceException;

class InvalidResponseException extends TransactionServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::TRANSACTION_SERVICE_INVALID_RESPONSE_EXCEPTION;

    /**
     * FraudAdviceApiException constructor.
     * @param string $message Message
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $message)
    {
        parent::__construct(null, $message);
    }
}