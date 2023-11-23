<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;

class RetrieveFraudRecommendationForNewMemberOnInitTranslatingService implements RetrieveFraudRecommendationForNewMemberOnInit
{
    public const EVENT_INIT_VISITOR = 'InitVisitor';

    /**
     * @var FraudRecommendationAdapter
     */
    private $adapter;


    /**
     * RetrieveAdviceForNewMemberOnInitTranslatingService constructor.
     * @param FraudRecommendationAdapter $adapter Fraud Recommendation Adapter
     */
    public function __construct(FraudRecommendationAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param BusinessGroupId $businessGroupId Business Group Id
     * @param SiteId          $siteId          Site Id
     * @param Ip              $ip              Ip
     * @param CountryCode     $countryCode     Country Code
     * @param SessionId       $sessionId       Session Id
     * @param array           $fraudHeaders    Fraud headers
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
    ): FraudRecommendationCollection {

        return $this->adapter->retrieve(
            (string) $businessGroupId,
            (string) $siteId,
            self::EVENT_INIT_VISITOR,
            [
                'ip'                   => [(string) $ip],
                'countryCode'          => [(string) $countryCode],
            ],
            (string) $sessionId,
            $fraudHeaders
        );
    }
}
