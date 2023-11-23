<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

interface FraudCsAdapter
{
    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $sessionId                 Session Id
     * @return void
     */
    public function retrieveAdvice(PaymentTemplateCollection $paymentTemplateCollection, string $sessionId): void;
}
