<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\ApiException;
use CommonServices\FraudServiceClient\Model\FraudRequestDto;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AzureActiveDirectoryAccessToken;

/**
 * @deprecated
 * Class FraudAdviceCsClient
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs
 */
class FraudAdviceCsClient extends ServiceClient
{
    private $fraudServiceApi;

    /**
     * FraudServiceCsClient constructor.
     * @param AdviceApi $fraudServiceApi FraudAdviceAPI from CS
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(AdviceApi $fraudServiceApi)
    {
        $this->fraudServiceApi = $fraudServiceApi;
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

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $sessionId                 Session Id
     * @return mixed
     * @throws Exceptions\FraudAdviceCsCodeApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieve(PaymentTemplateCollection $paymentTemplateCollection, string $sessionId)
    {
        $this->fraudServiceApi->getConfig()->setApiKey('Authorization', $this->generateToken());
        try {
            return $this->fraudServiceApi->apiV1AdvicePost(
                new FraudRequestDto(
                    [
                        'sessionId' => $sessionId,
                        'rules'     => [
                            [
                                'name'       => 'safebin',
                                'attributes' => [
                                    'bin' => array_column($paymentTemplateCollection->toArray(), 'firstSix')
                                ]
                            ]
                        ]
                    ]
                )
            );
        } catch (ApiException $e) {
            throw new Exceptions\FraudAdviceCsCodeApiException($e, $e->getMessage(), $e->getCode());
        }
    }
}
