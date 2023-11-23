<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService;

use Exception;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Struct;
use Probiller\Common\ServiceData;
use Probiller\Common\Site;
use Probiller\Service\Config\BusinessGroupResponse;
use Probiller\Service\Config\GetBusinessGroupRequest;
use Probiller\Service\Config\GetSiteRequest;
use Probiller\Service\Config\ProbillerConfigClient;
use Probiller\Service\Config\SiteResponse;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site as DomainSite;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AzureActiveDirectoryAccessToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\CreateSiteException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\InvalidConfigServiceResponse;
use Throwable;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_UNAVAILABLE;

class ConfigService
{
    /**
     * @var ProbillerConfigClient
     */
    private $client;

    /**
     * ConfigService constructor.
     *
     * @param ProbillerConfigClient $client Client
     */
    public function __construct(ProbillerConfigClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return ProbillerConfigClient
     * @throws \ProBillerNG\Logger\Exception
     */
    public function getClient(): ProbillerConfigClient
    {
        $state = $this->client->getConnectivityState(true);
        Log::info('ConfigService Get connectivity state and try to connect', ['state' => $state]);

        return $this->client;
    }

    /**
     * @return string|null
     */
    private static function generateToken(): ?string
    {
        $azureADToken = new AzureActiveDirectoryAccessToken(
            config('clientapis.configService.aadAuth.clientId'),
            config('clientapis.configService.aadAuth.tenant')
        );

        return $azureADToken->getToken(
            config('clientapis.configService.aadAuth.clientSecret'),
            config('clientapis.configService.aadAuth.resource')
        );
    }

    /**
     * @return array
     */
    public static function getMetadata(): array
    {
        return ['Authorization' => ['Bearer ' . ConfigService::generateToken()]];
    }

    /**
     * @param string $siteId Site Id
     *
     * @return DomainSite|null
     * @throws Exception
     */
    public function getSite(string $siteId): ?DomainSite
    {
        /**
         * @var SiteResponse $siteConfigReply
         */
        $siteConfigReply = $this->retrieveSite($siteId);

        if (!$siteConfigReply instanceof SiteResponse || !$siteConfigReply->getSite()) {
            return null;
        }

        $siteInfo = json_decode($siteConfigReply->serializeToJsonString(), true);

        if (!isset($siteInfo['site']) && !isset($siteInfo['site']['businessGroupId'])) {
            throw new InvalidConfigServiceResponse('No businessGroupId found in response body');
        }

        $businessGroupConfigReply = $this->retrieveSiteBusinessGroup($siteInfo['site']['businessGroupId']);

        if (!$businessGroupConfigReply instanceof BusinessGroupResponse) {
            return null;
        }

        $businessGroupArray = json_decode($businessGroupConfigReply->serializeToJsonString(), true);

        if (!isset($siteInfo['site']['serviceCollection'])) {
            throw new InvalidConfigServiceResponse('No serviceCollection found in response body');
        }

        try {
            $serviceCollection = $this->createServiceCollection($siteConfigReply->getSite()->getServiceCollection());

            return DomainSite::create(
                SiteId::createFromString($siteInfo['site']['siteId']),
                BusinessGroupId::createFromString($siteInfo['site']['businessGroupId']),
                $siteInfo['site']['url'],
                $siteInfo['site']['name'],
                $siteConfigReply->getSite()->getPhoneNumber(),
                $siteConfigReply->getSite()->getSkypeNumber(),
                $siteConfigReply->getSite()->getSupportLink(),
                $siteConfigReply->getSite()->getMailSupportLink(),
                $siteConfigReply->getSite()->getMessageSupportLink(),
                $siteConfigReply->getSite()->getCancellationLink(),
                $siteInfo['site']['postbackUrl'],
                $serviceCollection,
                $businessGroupArray['businessGroup']['privateKey'],
                $this->createPublicKeyCollection($businessGroupArray['businessGroup']['publicKeys']),
                $siteConfigReply->getSite()->getDescription(),
                $siteConfigReply->getSite()->getIsStickyGateway(),
                $siteConfigReply->getSite()->getIsNsfSupported(),
                $siteConfigReply->getSite()->getNumberOfAttempts()
            );
        } catch (Throwable $e) {
            Log::error(
                'Error occurred while creating Site model',
                [
                    'error' => $e->getMessage(),
                ]
            );

            throw new CreateSiteException('Error occurred while creating Site model based on Config Service response');
        }
    }

    /**
     * @param RepeatedField $services Services
     *
     * @return ServiceCollection
     */
    private function createServiceCollection(RepeatedField $services): ServiceCollection
    {
        $serviceCollection = new ServiceCollection();

        $iterator = $services->getIterator();

        while ($iterator->valid()) {
            /** @var ServiceData $serviceData */
            $serviceData = $iterator->current();

            $options = [];
            if ($serviceData->getOptions() instanceof Struct) {
                $options = json_decode($serviceData->getOptions()->serializeToString(), true);
            }

            $serviceCollection->add(
                Service::create(
                    $serviceData->getName(),
                    $serviceData->getEnabled(),
                    $options
                )
            );
            $iterator->next();
        }

        return $serviceCollection;
    }

    /**
     * @param array $publicKeys Public Keys
     *
     * @return PublicKeyCollection
     * @throws Exception
     */
    private function createPublicKeyCollection(array $publicKeys): PublicKeyCollection
    {
        $publicKeyCollection = new PublicKeyCollection();

        if (empty($publicKeys)) {
            return $publicKeyCollection;
        }

        foreach ($publicKeys as $key => $publicKey) {
            $publicKeyCollection->add(
                PublicKey::create(
                    KeyId::createFromString($publicKey),
                    \DateTimeImmutable::createFromMutable(
                        new \DateTime()
                    )
                )
            );
        }

        return $publicKeyCollection;
    }

    /**
     * @param string $siteId Site Id
     *
     * @return Site|null
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    private function retrieveSite(string $siteId): ?SiteResponse
    {
        try {
            $siteConfigRequest = new GetSiteRequest();

            $siteConfigRequest->setSiteId($siteId);

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                Log::info(
                    'RetrieveSite REQUEST starting retrieve site configuration from config service',
                    [
                        'requestType'    => 'GRPC',
                        'method'         => 'GetSiteConfig',
                        'host'           => env('CONFIG_SERVICE_HOST'),
                        'attempt'        => $attempt,
                        'requestPayload' => [
                            'siteId' => $siteId,
                        ],
                    ]
                );

                [$siteConfigReply, $responseStatus] = $this->client->GetSiteConfig(
                    $siteConfigRequest,
                    $this->addHeaders()
                )->wait();

                if ($responseStatus->code !== STATUS_UNAVAILABLE) {
                    break;
                }
            }

            if ($responseStatus->code !== STATUS_OK || !$siteConfigReply instanceof SiteResponse) {
                Log::info(
                    'RetrieveSite RESPONSE no Site was returned by Config Service',
                    [
                        'requestType'  => 'GRPC',
                        'method'       => 'GetSiteConfig',
                        'host'         => env('CONFIG_SERVICE_HOST'),
                        'responseCode' => $responseStatus->code,
                        'details'      => $responseStatus->details,
                    ]
                );

                return null;
            }

            $siteConfigInfo = json_decode($siteConfigReply->serializeToJsonString(), true);

            Log::info(
                'RetrieveSite RESPONSE site configuration from config service',
                [
                    'responseType' => 'GRPC',
                    'method'       => 'GetSiteConfig',
                    'host'         => env('CONFIG_SERVICE_HOST'),
                    'response'     => [
                        'payload' => DataObfuscatorHelper::obfuscateSensitiveData(
                            $siteConfigInfo,
                            [
                                'privateKey',
                                'publicKeys',
                            ]
                        ),
                    ],
                ]
            );

            return $siteConfigReply;
        } catch (Throwable $e) {
            Log::error(
                'RetrieveSite Error occurred while retrieving Site from Config Service',
                [
                    'error' => $e->getMessage(),
                ]
            );

            throw new InvalidConfigServiceResponse('Error occurred while retrieving Site from Config Service');
        }
    }

    /**
     * @param string $businessGroupId Business Group Id
     *
     * @return BusinessGroupResponse|null
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    private function retrieveSiteBusinessGroup(string $businessGroupId): ?BusinessGroupResponse
    {
        try {
            $businessGroupRequest = new GetBusinessGroupRequest();
            $businessGroupRequest->setBusinessGroupId($businessGroupId);

            Log::info(
                'RetrieveSiteBusinessGroup REQUEST retrieving business group from config service',
                [
                    'requestType'    => 'GRPC',
                    'method'         => 'GetBusinessGroupConfig',
                    'host'           => env('CONFIG_SERVICE_HOST'),
                    'requestPayload' => $businessGroupRequest->serializeToJsonString(),
                ]
            );

            $businessGroupResponse = $this->client->GetBusinessGroupConfig(
                $businessGroupRequest,
                $this->addHeaders()
            )->wait();

            [$businessGroupConfigReply, $businessGroupResponseStatus] = $businessGroupResponse;

            if ($businessGroupResponseStatus->code !== STATUS_OK
                || !$businessGroupConfigReply instanceof BusinessGroupResponse
            ) {
                Log::info(
                    'RetrieveSiteBusinessGroup RESPONSE no business group was returned by Config Service',
                    [
                        'requestType'  => 'GRPC',
                        'method'       => 'GetBusinessGroupConfig',
                        'host'         => env('CONFIG_SERVICE_HOST'),
                        'responseCode' => $businessGroupResponseStatus->code,
                        'details'      => $businessGroupResponseStatus->details,
                    ]
                );

                return null;
            }

            $businessGroupConfigInfo = json_decode($businessGroupConfigReply->serializeToJsonString(), true);
            Log::info(
                'RetrieveSiteBusinessGroup RESPONSE business group from config service',
                [
                    'responseType' => 'GRPC',
                    'method'       => 'GetBusinessGroupConfig',
                    'host'         => env('CONFIG_SERVICE_HOST'),
                    'response'     => [
                        'payload' => DataObfuscatorHelper::obfuscateSensitiveData(
                            $businessGroupConfigInfo,
                            [
                                'privateKey',
                                'publicKeys',
                            ]
                        ),
                    ],
                ]
            );

            return $businessGroupConfigReply;
        } catch (Throwable $e) {
            Log::error(
                'RetrieveSiteBusinessGroup Error occurred while retrieving BusinessGroup from Config Service',
                [
                    'error' => $e->getMessage(),
                ]
            );

            throw new InvalidConfigServiceResponse('Error occurred while retrieving BusinessGroup from Config Service');
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    private function addHeaders(): array
    {
        return array_merge(
            [
                'x-correlation-id' => [
                    Log::getCorrelationId(),
                ],
            ],
            ConfigService::getMetadata()
        );
    }
}
