<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\BinRoutingService;

class LaravelBinRoutingServiceFactory
{
    /**
     * @param string $billerName biller name
     * @return mixed
     * @throws UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function get(string $billerName): BinRoutingService
    {
        switch ($billerName) {
            case RocketgateBiller::BILLER_NAME:
                $translatingService = app()->make(RocketgateBinRoutingTranslatingService::class);
                break;
            case NetbillingBiller::BILLER_NAME:
                $translatingService = app()->make(NetbillingBinRoutingTranslatingService::class);
                break;
            default:
                throw new UnknownBillerNameException($billerName);
        }

        return $translatingService;
    }
}
