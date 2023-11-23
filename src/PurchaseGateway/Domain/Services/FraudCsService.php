<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

interface FraudCsService
{
    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $sessionId                 Session Id
     * @return void
     */
    public function retrieveAdvice(PaymentTemplateCollection $paymentTemplateCollection, string $sessionId): void;

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $siteId                    Site ID
     * @param int                       $initialDays                Initial Days
     *
     * @return void
     */
    public function retrieveAdviceFromConfig(
        PaymentTemplateCollection $paymentTemplateCollection,
        string $siteId,
        int $initialDays
    ): void;
}
