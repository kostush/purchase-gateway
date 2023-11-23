<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use GuzzleHttp\Client;
use MindGeek\Aad\AzureActiveDirectoryHelper;
use Illuminate\Support\Facades\Log;
use ProBillerNG\PurchaseGateway\Application\Services\RequestToken;

class AzureActiveDirectoryAccessToken implements RequestToken
{
    /** @var int */
    public const TTL_SAFETY_BUFFER = 600;

    /** @var JsonWebTokenGenerator */
    protected $azureHelper;

    /** @var Client */
    protected $client;

    /** @var string */
    protected $apcuKey;

    /**
     * AzureActiveDirectoryAccessToken constructor.
     *
     * @param string      $clientId Client ID
     * @param string      $tenant   Tenant ID
     * @param Client|null $client   Client
     */
    public function __construct(string $clientId, string $tenant, ?Client $client = null)
    {
        $this->apcuKey = 'AADToken_' . $clientId;
        $this->client  = $client;

        $this->azureHelper = new AzureActiveDirectoryHelper(
            $clientId,
            $tenant,
            ['httpClient' => $client]
        );
    }

    /**
     * @return string
     */
    public function apcuKey(): string
    {
        return $this->apcuKey;
    }

    /**
     * @param string $clientSecret Client Secret
     * @param string $resource     Resource
     * @param bool   $skipCache    Skip Cache
     *
     * @return string|null
     */
    public function getToken(string $clientSecret, string $resource, bool $skipCache = false): ?string
    {
        $accessToken = null;
        try {
            $accessToken = apcu_fetch($this->apcuKey());

            if (!$accessToken || $skipCache) {
                $response    = $this->azureHelper->getToken($clientSecret, $resource);
                $accessToken = $response->access_token;
                apcu_store($this->apcuKey(), $accessToken, ($response->expires_in - self::TTL_SAFETY_BUFFER));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return $accessToken ?: null;
    }
}
