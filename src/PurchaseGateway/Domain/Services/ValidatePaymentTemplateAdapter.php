<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;

interface ValidatePaymentTemplateAdapter
{
    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $lastFour          Last four
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     */
    public function validatePaymentTemplate(
        string $paymentTemplateId,
        string $lastFour,
        string $sessionId
    ): PaymentTemplate;
}
