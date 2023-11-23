<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;

interface PaymentInfoFactory
{
    /**
     * @param string      $paymentType       Payment type
     * @param string|null $paymentMethod     Payment method
     * @param string|null $cardHash          Card Hash
     * @param string|null $paymentTemplateId Payment template id
     * @return PaymentInfo
     */
    public static function create(
        string $paymentType,
        ?string $paymentMethod,
        ?string $cardHash = null,
        ?string $paymentTemplateId = null
    ): PaymentInfo;
}
