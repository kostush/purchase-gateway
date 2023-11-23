<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use ProbillerNG\BillerMappingServiceClient\Model\SuccessResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFieldsFactoryService;

class BillerMappingTranslator
{
    /**
     * @param SuccessResponse $response Success Response
     * @return BillerMapping
     * @throws \Exception
     */
    public function translate(SuccessResponse $response): BillerMapping
    {
        $biller = BillerFactoryService::create($response->getBillerName());

        $billerFieldsData = $response->getBillerFields();

        $billerFields = BillerFieldsFactoryService::create($biller, $billerFieldsData);

        return BillerMapping::create(
            SiteId::createFromString($response->getSiteId()),
            BusinessGroupId::createFromString($response->getBusinessGroupId()),
            $response->getCurrencyCode(),
            $response->getBillerName(),
            $billerFields
        );
    }
}
