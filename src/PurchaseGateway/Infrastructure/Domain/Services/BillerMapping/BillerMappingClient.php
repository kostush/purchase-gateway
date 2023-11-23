<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use ProbillerNG\BillerMappingServiceClient\ApiException;
use ProbillerNG\BillerMappingServiceClient\Model\SuccessResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProbillerNG\BillerMappingServiceClient\Api\BillerMappingApi;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping\Exceptions\BillerMappingApiException;

class BillerMappingClient extends ServiceClient
{
    /**
     * @var BillerMappingApi
     */
    private $billerMappingApi;

    /**
     * BillerMappingClient constructor.
     *
     * @param BillerMappingApi $billerMappingApi BillerMappingApi
     */
    public function __construct(BillerMappingApi $billerMappingApi)
    {
        $this->billerMappingApi = $billerMappingApi;
    }

    /**
     * @param string $billerName BillerName
     * @param string $businessGroupId
     * @param string $siteId SiteId
     * @param string $currencyCode CurrencyCode
     * @param string $sessionId SessionId
     * @return SuccessResponse
     * @throws BillerMappingApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieve(
        string $billerName,
        string $businessGroupId,
        string $siteId,
        string $currencyCode,
        string $sessionId
    ): SuccessResponse {
        try {
            return $this->billerMappingApi->retrieveGroupSiteBillerMapping(
                $billerName,
                $businessGroupId,
                $siteId,
                $currencyCode,
                $sessionId
            );
        } catch (ApiException $exception) {
            throw new BillerMappingApiException($exception);
        }
    }
}
