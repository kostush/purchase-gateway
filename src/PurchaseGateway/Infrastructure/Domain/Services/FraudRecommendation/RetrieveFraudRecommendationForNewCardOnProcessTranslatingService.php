<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\NonPCIPaymentFormData;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewPaymentOnProcess;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;

class RetrieveFraudRecommendationForNewCardOnProcessTranslatingService implements RetrieveFraudRecommendationForNewPaymentOnProcess
{
    public const EVENT_PROCESS_CUSTOMER = 'ProcessCustomer';

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
    ): FraudRecommendationCollection {
        return $this->adapter->retrieve(
            (string) $businessGroupId,
            (string) $siteId,
            self::EVENT_PROCESS_CUSTOMER,
            $this->translatingData($nonPCIPaymentFormData, $totalAmount, $siteId),
            (string) $sessionId,
            $fraudHeaders
        );
    }

    /**
     * @param NonPCIPaymentFormData $nonPCIPaymentFormData
     * @param Amount                $totalAmount
     * @param SiteId                $siteId
     *
     * @return array
     */
    private function translatingData(
        NonPCIPaymentFormData $nonPCIPaymentFormData,
        Amount $totalAmount,
        SiteId $siteId
    ): array {
        $data = [
            'totalAmount' => [
                (string) $totalAmount->value()
            ],
            'bin'         => [
                !is_null($nonPCIPaymentFormData->bin()) ? (string) $nonPCIPaymentFormData->bin() : null
            ],
            'lastFour'    => [
                !is_null($nonPCIPaymentFormData->lastFour()) ? (string) $nonPCIPaymentFormData->lastFour() : null
            ],
            'firstName'   => [
                !is_null($nonPCIPaymentFormData->firstName()) ? (string) $nonPCIPaymentFormData->firstName() : null
            ],
            'lastName'    => [
                !is_null($nonPCIPaymentFormData->lastName()) ? (string) $nonPCIPaymentFormData->lastName() : null
            ],
            'email'       => [
                !is_null($nonPCIPaymentFormData->email()) ? (string) $nonPCIPaymentFormData->email() : null
            ],
            'address'     => [
                $nonPCIPaymentFormData->street()
            ],
            'city'        => [
                $nonPCIPaymentFormData->city()
            ],
            'state'       => [
                $nonPCIPaymentFormData->state()
            ],
            'zipCode'     => [
                !is_null($nonPCIPaymentFormData->zip()) ? (string) $nonPCIPaymentFormData->zip() : null
            ],
            'countryCode' => [
                !is_null($nonPCIPaymentFormData->countryCode()) ? (string) $nonPCIPaymentFormData->countryCode() : null
            ],
            'siteId' => [
                (string) $siteId
            ],
        ];

        $domain = null;
        if (!is_null($nonPCIPaymentFormData->email()) && !is_null($nonPCIPaymentFormData->email()->domain())) {
            $domain = (string) $nonPCIPaymentFormData->email()->domain();
        }

        $data['domain'] = [$domain];

        return $data;
    }
}
