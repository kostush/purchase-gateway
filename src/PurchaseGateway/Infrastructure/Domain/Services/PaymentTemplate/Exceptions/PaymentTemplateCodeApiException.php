<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions;

use ProBillerNG\PurchaseGateway\Code;

class PaymentTemplateCodeApiException extends PaymentTemplateException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::PAYMENT_TEMPLATE_API_ERROR_EXCEPTION;
}
