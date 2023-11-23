<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProbillerNG\BinRoutingServiceClient\Model\RocketgateBinCard2;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;

class RocketgateBinRoutingAdapter implements BinRoutingServiceAdapter
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
     * @param string $merchantId       Merchant Id.
     * @param string $currency         Currency.
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
        string $merchantId,
        string $currency,
        int $joinSubmitNumber,
        string $itemId,
        string $businessGroupId
    ): BinRoutingCollection {
        $result = $this->binRoutingClient->retrieveRocketgateBinCard(
            (new RocketgateBinCard2())
                ->setBin($bin)
                ->setMerchantId($merchantId)
                ->setCurrency($currency)
                ->setJoinSubmitNumber($joinSubmitNumber)
                ->setBusinessGroupId($businessGroupId)
        );

        return $this->binRoutingTranslator->translate($result, $itemId);
    }
}
