<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class UnsupportedPaymentMethodException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::UNSUPPORTED_PAYMENT_METHOD;

    /**
     * UnsupportedPaymentMethodException constructor.
     * @param string          $paymentMethod Payment Method
     * @param \Throwable|null $previous      Previous Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $paymentMethod, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $paymentMethod);
    }
}
