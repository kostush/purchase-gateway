<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;

interface RetrievePaymentTemplateAdapter
{
    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     */
    public function retrievePaymentTemplate(
        string $paymentTemplateId,
        string $sessionId
    ): PaymentTemplate;
}
