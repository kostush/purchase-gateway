<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

interface RetrieveFraudRecommendationForExistingMemberOnInit
{
    /**
     * @param BusinessGroupId $businessGroupId
     * @param SiteId          $siteId
     * @param Ip              $ip
     * @param CountryCode     $countryCode
     * @param Amount          $totalAmount
     * @param SessionId       $sessionId
     * @param Email|null      $email
     * @param array           $fraudHeaders
     *
     * @return FraudRecommendationCollection
     */
    public function retrieve(
        BusinessGroupId $businessGroupId,
        SiteId $siteId,
        Ip $ip,
        CountryCode $countryCode,
        Amount $totalAmount,
        SessionId $sessionId,
        ?Email $email,
        array $fraudHeaders
    ): FraudRecommendationCollection;
}
