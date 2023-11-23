<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\IncreasePurchaseAttempts;
use ProBillerNG\PurchaseGateway\Domain\Returns500Code;

class RetrievePaymentTemplateException extends PaymentTemplateException implements
    IncreasePurchaseAttempts,
    Returns500Code
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::RETRIEVE_PAYMENT_TEMPLATE_EXCEPTION;
}
