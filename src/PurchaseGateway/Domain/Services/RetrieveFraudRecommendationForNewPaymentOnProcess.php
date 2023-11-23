<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\NonPCIPaymentFormData;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

interface RetrieveFraudRecommendationForNewPaymentOnProcess
{
    /**
     * @param BusinessGroupId       $businessGroupId
     * @param SiteId                $siteId
     * @param NonPCIPaymentFormData $nonPCIPaymentFormData
     * @param Amount                $totalAmount
     * @param SessionId             $sessionId
     * @param array                 $fraudHeaders
     *
     * @return FraudRecommendationCollection
     */
    public function retrieve(
        BusinessGroupId $businessGroupId,
        SiteId $siteId,
        NonPCIPaymentFormData $nonPCIPaymentFormData,
        Amount $totalAmount,
        SessionId $sessionId,
        array $fraudHeaders
    ): FraudRecommendationCollection;
}