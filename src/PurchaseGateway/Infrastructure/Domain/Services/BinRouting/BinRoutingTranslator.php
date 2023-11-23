<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProbillerNG\BinRoutingServiceClient\Model\BadRequestError;
use ProbillerNG\BinRoutingServiceClient\Model\Error;
use ProbillerNG\BinRoutingServiceClient\Model\MethodNotAllowedResponse;
use ProbillerNG\BinRoutingServiceClient\Model\RoutingCodeItem;

class BinRoutingTranslator
{
    /**
     * @param BadRequestError|Error|MethodNotAllowedResponse|RoutingCodeItem[] $result The api call result
     * @param string                                                           $itemId The item id that will be
     *                                                                                 associated with the routing codes
     * @return mixed
     * @throws Exceptions\BinRoutingCodeErrorException
     * @throws Exceptions\BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function translate($result, string $itemId): BinRoutingCollection
    {
        if ($result instanceof Error) {
            throw new Exceptions\BinRoutingCodeErrorException(null, $result->getMessage(), $result->getCode());
        }

        if ($result instanceof BadRequestError) {
            throw new Exceptions\BinRoutingCodeErrorException(null, $result->getMessage(), $result->getCode());
        }

        if ($result instanceof MethodNotAllowedResponse) {
            throw new Exceptions\BinRoutingCodeErrorException(null, $result->getMessage(), $result->getCode());
        }

        $binRoutingCodeCollection = new BinRoutingCollection();
        if (is_array($result)) {
            foreach ($result as $routingCode) {
                if (!($routingCode instanceof RoutingCodeItem)) {
                    throw new Exceptions\BinRoutingCodeTypeException(
                        null,
                        RoutingCodeItem::class,
                        500
                    );
                }

                //set the collection index by item id and attempt number
                //so it can be easily retrieved by the transaction infrastructure service
                $binRoutingCodeCollection->offsetSet(
                    $itemId . '_' . (int) $routingCode->getAttempt(),
                    BinRouting::create(
                        (int) $routingCode->getAttempt(),
                        $routingCode->getRoutingCode(),
                        $routingCode->getBankName()
                    )
                );
            }
        }

        return $binRoutingCodeCollection;
    }
}
