<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

/**
 * Class PaymentTemplateValidationTranslator
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation
 */
class PaymentTemplateValidationTranslator
{
    /**
     * @param PaymentTemplateCollection $templateCollection
     * @param array                     $paymentTemplateInfo
     * @param int                       $initialDays
     *
     * @return void
     */
    public static function translate(
        PaymentTemplateCollection $templateCollection,
        array $paymentTemplateInfo,
        int $initialDays
    ): void {
        if (!empty($initialDays) && isset($paymentTemplateInfo['subscriptionPurchaseEnabled'])) {
            $itShouldValidate = $paymentTemplateInfo['subscriptionPurchaseEnabled'];
            $templateCollection->setAllSafeBins(!$itShouldValidate);
        }

        if (empty($initialDays) && isset($paymentTemplateInfo['singleChargePurchaseEnabled'])) {
            $itShouldValidate = $paymentTemplateInfo['singleChargePurchaseEnabled'];
            $templateCollection->setAllSafeBins(!$itShouldValidate);
        }
    }
}
