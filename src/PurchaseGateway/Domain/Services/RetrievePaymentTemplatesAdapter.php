<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

interface RetrievePaymentTemplatesAdapter
{
    /**
     * @param string $memberId    Member Id
     * @param string $paymentType Payment type
     * @param string $sessionId   Session Id
     * @return PaymentTemplateCollection
     */
    public function retrieveAllPaymentTemplates(
        string $memberId,
        string $paymentType,
        string $sessionId
    ): PaymentTemplateCollection;
}
