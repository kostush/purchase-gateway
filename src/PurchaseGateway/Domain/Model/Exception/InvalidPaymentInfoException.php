<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidPaymentInfoException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_PAYMENT_INFORMATION;

    /**
     * InvalidCreditCardExpirationDate constructor.
     * @param string          $field    The invalid field
     * @param \Throwable|null $previous Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $field, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $field);
    }
}
