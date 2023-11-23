<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\ApiException;
use CommonServices\FraudServiceClient\Model\AdviceRequestDto;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AzureActiveDirectoryAccessToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeApiException;

class FraudRecommendationClient extends ServiceClient
{
    private $client;

    /**
     * FraudServiceCsClient constructor.
     * @param AdviceApi $fraudServiceApi FraudAdviceAPI from CS
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(AdviceApi $fraudServiceApi)
    {
        $this->client = $fraudServiceApi;
    }

    /**
     * @param string $businessGroupId Business Group Id
     * @param string $siteId          Site Id
     * @param string $event           Event
     * @param array  $data            Data array
     * @param string $sessionId       Session Id
     * @param array  $fraudHeaders    Fraud headers
     *
     * @return array
     * @throws Exception
     * @throws FraudAdviceCsCodeApiException
     */
    public function retrieve(
        string $businessGroupId,
        string $siteId,
        string $event,
        array $data,
        string $sessionId,
        array $fraudHeaders
    ): array {
        $this->client->getConfig()->setApiKey('Authorization', $this->generateToken());

        $identifier        = (!isset($data['email']) || !is_array($data['email'])) ? $sessionId : end($data['email']);
        $fraudParamRequest = [
            'identifier'      => $identifier,
            'sessionId'       => $sessionId,
            'businessGroupId' => $businessGroupId,
            'siteId'          => $siteId,
            'event'           => $event,
            'data'            => array_merge($data, $fraudHeaders),
        ];

        Log::info("FraudRecommendation Retrieving fraud recommendation for the following params: ",
            [
                'hostWithNoPath'    => $this->client->getConfig()->getHost(),
                'payload'           => $fraudParamRequest
            ]
        );

        try {
            $response = $this->client->apiV3AdvicePostWithHttpInfo(
                new AdviceRequestDto(
                    $fraudParamRequest
                )
            );

            Log::info('FraudRecommendation Retrieved fraud recommendation from fraud service', $response ?? []);

            return $response;
        } catch (ApiException $e) {
            Log::warning('FraudRecommendation Common Fraud Service Communication Error',['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @return string|null
     * @throws \ProBillerNG\Logger\Exception
     */
    private function generateToken(): ?string
    {
        $azureADToken = new AzureActiveDirectoryAccessToken(
            config('clientapis.fraudServiceCs.aadAuth.clientId'),
            config('clientapis.fraudServiceCs.aadAuth.tenant')
        );

        return $azureADToken->getToken(
            config('clientapis.fraudServiceCs.aadAuth.clientSecret'),
            config('clientapis.fraudServiceCs.aadAuth.resource')
        );
    }
}
