<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

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
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingCardOnProcess;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;

class RetrieveFraudRecommendationForExistingCardOnProcessTranslatingService implements RetrieveFraudRecommendationForExistingCardOnProcess
{
    const EVENT_PROCESS_CUSTOMER = 'ProcessCustomer';

    /**
     * @var FraudRecommendationAdapter
     */
    private $adapter;

    /**
     * RetrieveAdviceForNewMemberOnInitTranslatingService constructor.
     * @param FraudRecommendationAdapter $adapter
     */
    public function __construct(FraudRecommendationAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

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
    ): FraudRecommendationCollection {
        return $this->adapter->retrieve(
            (string)$businessGroupId,
            (string)$siteId,
            self::EVENT_PROCESS_CUSTOMER,
            [
                'ip'          => [(string) $ip],
                'countryCode' => [(string) $countryCode],
                'email'       => [(string) $email],
                'bin'         => [(string) $bin],
                'lastFour'    => [(string) $lastFour],
                'totalAmount' => [(string) $totalAmount->value()],
                'siteId'      => [(string) $siteId]
            ],
            (string)$sessionId,
            $fraudHeaders
        );
    }
}
