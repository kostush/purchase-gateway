<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidPaymentTemplateId extends PaymentTemplateValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_PAYMENT_TEMPLATE_ID;
}
