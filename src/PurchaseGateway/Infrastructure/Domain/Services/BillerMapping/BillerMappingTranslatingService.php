<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use Probiller\Common\BillerMappingFilters;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\BillerMapping\BillerMappingTranslator as ConfigServiceBillerMappingTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use const Grpc\STATUS_OK;

class BillerMappingTranslatingService implements BillerMappingService
{
    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * BillerMappingTranslatingService constructor.
     *
     * @param ConfigService $configService ConfigService
     */
    public function __construct(
        ConfigService $configService
    ) {
        $this->configService = $configService;
    }

    /**
     * @param string $billerName      Biller name
     * @param string $businessGroupId Business group id
     * @param string $siteId          Site id
     * @param string $currencyCode    Currency code
     * @param string $sessionId       Session id
     *
     * @return BillerMapping
     * @throws BillerMappingException
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    public function retrieveBillerMapping(
        string $billerName,
        string $businessGroupId,
        string $siteId,
        string $currencyCode,
        string $sessionId
    ): BillerMapping {
        Log::info(
            'RetrieveBillerMapping REQUEST retrieving biller mappings from config service',
            [
                'requestType'    => 'GRPC',
                'method'         => 'GetBillerMappingConfigFiltered',
                'host'           => env('CONFIG_SERVICE_HOST'),
                'requestPayload' => [
                    'siteId'       => $siteId,
                    'billerName'   => $billerName,
                    'currencyCode' => $currencyCode
                ],
                'sessionId'      => $sessionId
            ]
        );

        $filters = $this->getBillerMappingFilters($siteId, $billerName, $currencyCode);

        /**
         * @var \Probiller\Common\BillerMapping $billerMapping
         */
        [
            $billerMapping,
            $responseStatus
        ] = $this->configService->getClient()->GetBillerMappingConfigFiltered(
            $filters,
            $this->configService->getMetadata()
        )->wait();

        if ($responseStatus->code == STATUS_OK) {
            Log::info(
                'RetrieveBillerMapping RESPONSE billerMapping retrieved from config service with success',
                [
                    'requestType'     => 'GRPC',
                    'method'          => 'GetBillerMappingConfigFiltered',
                    'host'            => env('CONFIG_SERVICE_HOST'),
                    'responsePayload' => $billerMapping->serializeToJsonString(),
                    'sessionId'       => $sessionId,
                    'status'          => 'OK'
                ]
            );

            return ConfigServiceBillerMappingTranslator::translate(
                $billerMapping,
                $currencyCode,
                $businessGroupId,
                $siteId
            );
        }

        Log::info(
            'RetrieveBillerMapping RESPONSE fail to retrieve BillerMapping from config service',
            [
                'requestType'  => 'GRPC',
                'method'       => 'GetBillerMappingConfigFiltered',
                'host'         => env('CONFIG_SERVICE_HOST'),
                'sessionId'    => $sessionId,
                'responseCode' => $responseStatus->code,
                'details'      => $responseStatus->details
            ]
        );

        throw new BillerMappingException();
    }

    /**
     * @param string $siteId       Site id
     * @param string $billerName   Biller name
     * @param string $currencyCode Currency code
     *
     * @return BillerMappingFilters
     */
    private function getBillerMappingFilters(
        string $siteId,
        string $billerName,
        string $currencyCode
    ): BillerMappingFilters {
        $filters = new BillerMappingFilters(
            [
                'siteId'     => $siteId,
                'billerName' => $billerName,
                'currency'   => $currencyCode
            ]
        );

        return $filters;
    }
}
