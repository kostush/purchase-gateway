<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

interface PaymentTemplateTranslatingService
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

    /**
     * @param string $paymentTemplateId Payment template Id
     * @param string $sessionId         Session Id
     * @return PaymentTemplate
     */
    public function retrievePaymentTemplate(
        string $paymentTemplateId,
        string $sessionId
    ): PaymentTemplate;

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
