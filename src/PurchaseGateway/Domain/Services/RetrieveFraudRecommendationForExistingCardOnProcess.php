<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

interface RetrieveFraudRecommendationForExistingCardOnProcess
{
    /**
     * @param BusinessGroupId $businessGroupId
     * @param SiteId          $siteId
     * @param CountryCode     $countryCode
     * @param Ip              $ip
     * @param Email           $email
     * @param Bin             $bin
     * @param LastFour        $lastFour
     * @param Amount          $totalAmount
     * @param SessionId       $sessionId
     * @param array           $fraudHeaders
     *
     * @return FraudRecommendationCollection
     */
    public function retrieve(
        BusinessGroupId $businessGroupId,
        SiteId $siteId,
        CountryCode $countryCode,
        Ip $ip,
        Email $email,
        Bin $bin,
        LastFour $lastFour,
        Amount $totalAmount,
        SessionId $sessionId,
        array $fraudHeaders
    ): FraudRecommendationCollection;
}
