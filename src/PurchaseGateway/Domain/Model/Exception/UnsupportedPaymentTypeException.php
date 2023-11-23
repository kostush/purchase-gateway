<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class UnsupportedPaymentTypeException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::UNSUPPORTED_PAYMENT_TYPE;

    /**
     * UnsupportedPaymentTypeException constructor.
     * @param string          $paymentType Payment type
     * @param \Throwable|null $previous    Previous Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $paymentType, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $paymentType);
    }
}
