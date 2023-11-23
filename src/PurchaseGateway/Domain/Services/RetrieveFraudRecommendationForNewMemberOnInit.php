<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

interface RetrieveFraudRecommendationForNewMemberOnInit
{
    /**
     * @param BusinessGroupId $businessGroupId
     * @param SiteId          $siteId
     * @param Ip              $ip
     * @param CountryCode     $countryCode
     * @param SessionId       $sessionId
     * @param array           $fraudHeaders
     *
     * @return FraudRecommendationCollection
     */
    public function retrieve(
        BusinessGroupId $businessGroupId,
        SiteId $siteId,
        Ip $ip,
        CountryCode $countryCode,
        SessionId $sessionId,
        array $fraudHeaders
    ): FraudRecommendationCollection;
}
