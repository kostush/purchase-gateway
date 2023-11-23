<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceException;

/**
 * Class MalformedPayloadException
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions
 */
class MalformedPayloadException extends TransactionServiceException
{
    /**
     * @var int $code Error code
     */
    protected $code =  Code::QYSSO_MALFORMED_PAYLOAD_EXCEPTION;

    /**
     * @var string $serviceName Service Name
     */
    public $serviceName = "Transaction Service";
}