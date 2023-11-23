<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProbillerNG\BinRoutingServiceClient\Model\NetbillingBinCard2;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;

class NetbillingBinRoutingAdapter implements BinRoutingServiceAdapter
{
    /**
     * @var BinRoutingClient
     */
    private $binRoutingClient;

    /**
     * @var BinRoutingTranslator
     */
    private $binRoutingTranslator;

    /**
     * BinRoutingServiceAdapter constructor.
     *
     * @param BinRoutingClient     $binRoutingClient     The client
     * @param BinRoutingTranslator $binRoutingTranslator The translator
     */
    public function __construct(
        BinRoutingClient $binRoutingClient,
        BinRoutingTranslator $binRoutingTranslator
    ) {
        $this->binRoutingClient     = $binRoutingClient;
        $this->binRoutingTranslator = $binRoutingTranslator;
    }

    /**
     * @param string $bin              Bin Card.
     * @param string $accountId        Account ID
     * @param string $siteTag          SiteTag
     * @param int    $joinSubmitNumber Join Submit Number.
     * @param string $itemId           The item id
     * @param string $businessGroupId  The business group id
     * @return BinRoutingCollection
     * @throws Exceptions\BinRoutingCodeApiException
     * @throws Exceptions\BinRoutingCodeErrorException
     * @throws Exceptions\BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieve(
        string $bin,
        string $accountId,
        string $siteTag,
        int $joinSubmitNumber,
        string $itemId,
        string $businessGroupId
    ): BinRoutingCollection {

        $netbillingBinCard = (new NetbillingBinCard2())
            ->setBin($bin)
            ->setAccountId($accountId)
            ->setSiteTag($siteTag)
            ->setJoinSubmitNumber($joinSubmitNumber)
            ->setBusinessGroupId($businessGroupId);

        $result = $this->binRoutingClient->retrieveNetbillingBinCard($netbillingBinCard);

        return $this->binRoutingTranslator->translate($result, $itemId);
    }
}
