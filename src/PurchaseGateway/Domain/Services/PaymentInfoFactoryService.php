<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingOtherPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\Logger\Exception;

class PaymentInfoFactoryService implements PaymentInfoFactory
{
    /**
     * @param string      $paymentType       Payment type
     * @param string|null $paymentMethod     Payment method
     * @param string|null $cardHash          Card Hash
     * @param string|null $paymentTemplateId Payment template id
     * @return PaymentInfo
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     */
    public static function create(
        string $paymentType,
        ?string $paymentMethod,
        ?string $cardHash = null,
        ?string $paymentTemplateId = null
    ): PaymentInfo {
        if ($paymentType === CCPaymentInfo::PAYMENT_TYPE) {
            if (!empty($cardHash)) {
                return ExistingCCPaymentInfo::create($cardHash, $paymentTemplateId, $paymentMethod, null);
            }

            return CCPaymentInfo::build($paymentType, $paymentMethod);
        }

        if (!empty($paymentTemplateId)) {
            return ExistingOtherPaymentInfo::create($paymentTemplateId, $paymentType, $paymentMethod);
        }
        return OtherPaymentTypeInfo::build($paymentType, $paymentMethod);
    }
}
