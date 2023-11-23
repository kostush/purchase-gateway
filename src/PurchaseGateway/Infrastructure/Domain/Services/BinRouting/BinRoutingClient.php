<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProbillerNG\BinRoutingServiceClient\Api\BinRoutingApi;
use ProbillerNG\BinRoutingServiceClient\ApiException;
use ProbillerNG\BinRoutingServiceClient\Model\BadRequestError;
use ProbillerNG\BinRoutingServiceClient\Model\Error;
use ProbillerNG\BinRoutingServiceClient\Model\MethodNotAllowedResponse;
use ProbillerNG\BinRoutingServiceClient\Model\NetbillingBinCard2;
use ProbillerNG\BinRoutingServiceClient\Model\RocketgateBinCard2;
use ProbillerNG\BinRoutingServiceClient\Model\RoutingCodeItem;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;

class BinRoutingClient extends ServiceClient
{
    /**
     * @var BinRoutingApi
     */
    private $binRoutingApi;

    /**
     * RoutingCodeSearchForPurchaseProcessClient constructor.
     *
     * @param BinRoutingApi $binRoutingApi The bin routing api
     */
    public function __construct(BinRoutingApi $binRoutingApi)
    {
        $this->binRoutingApi = $binRoutingApi;
    }

    /**
     * @param RocketgateBinCard2 $binCard The card bin
     * @return BadRequestError|Error|MethodNotAllowedResponse|RoutingCodeItem[]
     * @throws Exceptions\BinRoutingCodeApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveRocketgateBinCard(RocketgateBinCard2 $binCard)
    {
        try {
            return $this->binRoutingApi->rocketgateRoutingCodesByBusinessGroup($binCard, Log::getSessionId());
        } catch (ApiException $exception) {
            throw  new Exceptions\BinRoutingCodeApiException(null, $exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param NetbillingBinCard2 $binCard The card bin
     *
     * @return BadRequestError|Error|MethodNotAllowedResponse|RoutingCodeItem[]
     * @throws Exceptions\BinRoutingCodeApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveNetbillingBinCard(NetbillingBinCard2 $binCard)
    {
        try {
            return $this->binRoutingApi->netbillingRoutingCodesByBusinessGroup($binCard, Log::getSessionId());
        } catch (ApiException $exception) {
            throw  new Exceptions\BinRoutingCodeApiException(null, $exception->getMessage(), $exception->getCode());
        }
    }
}
