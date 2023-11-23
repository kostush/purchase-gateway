<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\IncreasePurchaseAttempts;
use ProBillerNG\PurchaseGateway\Domain\Returns500Code;

/**
 * Class PaymentTemplateDataNotFoundException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class PaymentTemplateDataNotFoundException extends PaymentTemplateException implements
    IncreasePurchaseAttempts,
    Returns500Code
{
    protected $code = Code::PAYMENT_TEMPLATE_DATA_NOT_FOUND_EXCEPTION;

    /**
     * PaymentTemplateDataNotFoundException constructor.
     * @param string          $paymentTemplateId Payment Template Id
     * @param \Throwable|null $previous          Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $paymentTemplateId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $paymentTemplateId);
    }
}
