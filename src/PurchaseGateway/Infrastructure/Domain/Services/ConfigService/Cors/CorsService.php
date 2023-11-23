<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors;

use Probiller\Service\Config\BusinessGroupList;
use Probiller\Service\Config\BusinessGroupResponse;
use Probiller\Service\Config\GetAllBusinessGroupsRequest;
use Probiller\Service\Config\GetAllSitesRequest;
use Probiller\Service\Config\ProbillerConfigClient;
use Probiller\Service\Config\SiteList;
use Probiller\Service\Config\SiteResponse;
use Illuminate\Support\Facades\Log;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\ConfigServiceNotOk;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_UNAVAILABLE;

class CorsService
{
    const ALLOWED_DOMAIN_CACHE_KEY         = 'cors.config-service';
    const ALLOWED_DOMAIN_CACHE_TTL_SECONDS = 1 * 60;

    /**
     * @var ProbillerConfigClient
     */
    private $probillerConfigClient;

    /**
     * CorsService constructor.
     *
     * @param ProbillerConfigClient $probillerConfigClient Probiller Config Client
     */
    public function __construct(ProbillerConfigClient $probillerConfigClient)
    {
        $this->probillerConfigClient = $probillerConfigClient;
    }

    /**
     * @return string[]
     * @throws ConfigServiceNotOk
     */
    public function getAllowedDomains(): array
    {
        if (apcu_exists(self::ALLOWED_DOMAIN_CACHE_KEY)) {
            Log::info('RetrieveAllowedDomains Retrieving CORS allowed domains from cache');

            return apcu_fetch(self::ALLOWED_DOMAIN_CACHE_KEY);
        }

        Log::info('RetrieveAllowedDomains Retrieving CORS allowed domains from config service');
        $allowedDomains = $this->retrieveAllowedDomainsFromConfigService();
        apcu_store(self::ALLOWED_DOMAIN_CACHE_KEY, $allowedDomains, self::ALLOWED_DOMAIN_CACHE_TTL_SECONDS);
        return $allowedDomains;
    }

    /**
     * @return array
     * @throws ConfigServiceNotOk
     */
    public function retrieveAllowedDomainsFromConfigService(): array
    {
        return array_unique(
            array_merge(
                $this->retrieveAllowedDomainsFromSites(),
                $this->retrieveAllowedDomainsFromBusinessGroup()
            )
        );
    }

    /**
     * @return SiteList
     * @throws ConfigServiceNotOk
     */
    protected function retrieveSiteList(): SiteList
    {
        $request = new GetAllSitesRequest();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Log::info(
                'RetrieveAllowedDomains REQUEST starting retrieve all site configuration from config service',
                [
                    'requestType'    => 'GRPC',
                    'method'         => 'GetAllSiteConfigs',
                    'host'           => env('CONFIG_SERVICE_HOST'),
                    'attempt'        => $attempt,
                    'requestPayload' => $request->serializeToJsonString()
                ]
            );

            /**
             * @var SiteList $siteList
             */
            [
                $siteList,
                $responseStatus
            ] = $this->probillerConfigClient->GetAllSiteConfigs(
                $request,
                ConfigService::getMetadata()
            )->wait();

            if ($responseStatus->code !== STATUS_UNAVAILABLE) {
                break;
            }
        }

        if ($responseStatus->code != STATUS_OK) {
            Log::error(
                'RetrieveAllowedDomains RESPONSE fail to retrieve all sites configuration from config service',
                [
                    'responseType'   => 'GRPC',
                    'method'         => 'GetAllSiteConfigs',
                    'requestPayload' => $request->serializeToJsonString(),
                    'responseCode'   => $responseStatus->code,
                    'details'        => $responseStatus->details,
                    'status'         => 'ERROR'
                ]
            );

            throw new ConfigServiceNotOk(
                'ConfigService failed to return site list. Status code: ' . $responseStatus->code .
                ', Details: ' . $responseStatus->details
            );
        }

        Log::info(
            'RetrieveAllowedDomains RESPONSE all sites configuration retrieved from config service',
            [
                'responseType' => 'GRPC',
                'method'       => 'GetAllSiteConfigs',
                'host'         => env('CONFIG_SERVICE_HOST'),
            ]
        );

        return $siteList;
    }

    /**
     * @param string $domain Domain
     *
     * @return string
     */
    public static function formatDomain(string $domain): string
    {
        if (empty($domain)) {
            return '';
        }

        $host = parse_url($domain, PHP_URL_HOST);

        if (!$host) {
            return '*.' . $domain;
        }

        return '*.' . str_ireplace(
            'www.',
            '',
            parse_url($domain, PHP_URL_HOST)
        );
    }

    /**
     * @return BusinessGroupList
     * @throws ConfigServiceNotOk
     */
    private function retrieveBusinessGroupList(): BusinessGroupList
    {
        $request = new GetAllBusinessGroupsRequest();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Log::info(
                'RetrieveAllowedDomains REQUEST starting retrieve all business group configuration from config service',
                [
                    'requestType'    => 'GRPC',
                    'method'         => 'GetAllBusinessGroupConfigs',
                    'host'           => env('CONFIG_SERVICE_HOST'),
                    'attempt'        => $attempt,
                    'requestPayload' => $request->serializeToJsonString()
                ]
            );

            /**
             * @var BusinessGroupList $businessGroup
             */
            [
                $businessGroup,
                $responseStatus
            ] = $this->probillerConfigClient->GetAllBusinessGroupConfigs(
                $request,
                ConfigService::getMetadata()
            )->wait();

            if ($responseStatus->code !== STATUS_UNAVAILABLE) {
                break;
            }
        }

        if ($responseStatus->code != STATUS_OK) {
            Log::error(
                'RetrieveAllowedDomains RESPONSE fail to retrieve all business group configuration from config service',
                [
                    'responseType'   => 'GRPC',
                    'method'         => 'GetAllBusinessGroupConfigs',
                    'requestPayload' => $request->serializeToJsonString(),
                    'responseCode'   => $responseStatus->code,
                    'details'        => $responseStatus->details,
                    'status'         => 'ERROR'
                ]
            );

            throw new ConfigServiceNotOk(
                'ConfigService failed to return business group list. Status code: ' . $responseStatus->code .
                ', Details: ' . $responseStatus->details
            );
        }

        Log::info(
            'RetrieveAllowedDomains RESPONSE all business group configuration retrieved from config service',
            [
                'responseType' => 'GRPC',
                'method'       => 'GetAllBusinessGroupConfigs',
                'host'         => env('CONFIG_SERVICE_HOST'),
            ]
        );

        return $businessGroup;
    }

    /**
     * @return array
     * @throws ConfigServiceNotOk
     */
    private function retrieveAllowedDomainsFromSites(): array
    {
        $domains = [];

        $siteList = $this->retrieveSiteList();

        /**
         * @var SiteResponse $site
         */
        foreach ($siteList->getValue() as $site) {
            if (!$site->getSite()) {
                continue;
            }
            foreach ($site->getSite()->getAllowedDomains() as $allowedDomain) {
                $domains[] = self::formatDomain($allowedDomain);
            }
        }

        return $domains;
    }

    /**
     * @return array
     * @throws ConfigServiceNotOk
     */
    private function retrieveAllowedDomainsFromBusinessGroup(): array
    {
        $domains = [];

        $bgList = $this->retrieveBusinessGroupList();

        /**
         * @var BusinessGroupResponse $bg
         */
        foreach ($bgList->getValue() as $bg) {
            if (!$bg->getBusinessGroup()) {
                continue;
            }
            foreach ($bg->getBusinessGroup()->getAllowedDomains() as $allowedDomain) {
                $domains[] = self::formatDomain($allowedDomain);
            }
        }

        return $domains;
    }
}
