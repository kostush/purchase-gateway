<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;

class RetrieveFraudRecommendationForExistingMemberOnInitTranslatingService implements RetrieveFraudRecommendationForExistingMemberOnInit
{
    public const EVENT_INIT_CUSTOMER = 'InitCustomer';

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
     * @param SiteId          $siteId Site Id
     * @param Ip              $ip Ip
     * @param CountryCode     $countryCode Country Code
     * @param Amount          $totalAmount Amount
     * @param SessionId       $sessionId Session Id
     * @param Email|null      $email Email
     * @param array           $fraudHeaders Fraud headers
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
    ): FraudRecommendationCollection {
        return $this->adapter->retrieve(
            (string) $businessGroupId,
            (string) $siteId,
            self::EVENT_INIT_CUSTOMER,
            [
                'ip'                   => [(string) $ip],
                'countryCode'          => [(string) $countryCode],
                'totalAmount'          => [(string) $totalAmount->value()],
                'email'                => [(string) ($email ?? '')],
            ],
            (string) $sessionId,
            $fraudHeaders
        );
    }
}
