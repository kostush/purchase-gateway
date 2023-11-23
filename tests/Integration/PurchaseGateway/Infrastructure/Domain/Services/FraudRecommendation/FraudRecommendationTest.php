<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\Configuration as FraudServiceCsConfiguration;
use CommonServices\FraudServiceClient\Model\AdviceRequestDto;
use CommonServices\SwallowServiceClient\Api\EventsApi;
use DateTime;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\EventIngestion\Infrastructure\Client\SwallowSystemClient;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchase3DSCompleted;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocity;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocityDeclined;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AzureActiveDirectoryAccessToken;
use Tests\IntegrationTestCase;
use ProBillerNG\EventIngestion\Infrastructure\AzureActiveDirectoryAccessToken as AzureToken;
use CommonServices\SwallowServiceClient\Configuration;

class FraudRecommendationTest extends IntegrationTestCase
{
    /**
     * @var AdviceApi
     */
    private $client;

    /**
     * @var EventIngestionService
     */
    private $eventIngestionService;
    /**
     * @var Client
     */
    private $clientGuz;

    /**
     * @throws \ProBillerNG\Logger\Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->client =
            new AdviceApi(
                new \GuzzleHttp\Client(
                    [
                        RequestOptions::CONNECT_TIMEOUT => 10,
                        RequestOptions::TIMEOUT         => 10,
                    ]
                ),
                (new FraudServiceCsConfiguration())
                    ->setApiKeyPrefix('Authorization', 'Bearer')
                    ->setHost('https://fraud-staging.mg.services')
            );

        $azureADToken = new AzureToken(
            config('clientapis.fraudServiceCs.aadAuth.clientId'),
            config('clientapis.fraudServiceCs.aadAuth.tenant')
        );

        $this->client->getConfig()->setApiKey('Authorization', $this->generateToken());

        $api = new EventsApi(
            new \GuzzleHttp\Client(),
            (new Configuration())->setHost('https://swallow-staging.mg.services')
        );
        $client = new SwallowSystemClient($api, $azureADToken);
        $this->eventIngestionService = new EventIngestionService($client);
    }

    /**
     * That’s a flaky test
     *
     * @test
     * @dataProvider emailsToForce3dsSoftBlocks
     * @param string $code
     * @param string $email
     * @throws \CommonServices\FraudServiceClient\ApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_receive_proper_code_according_static_email(string $code, string $email): void
    {
        $response = $this->getAdviceFromFraudRecommendation($email);
        $this->assertEquals($code, $response[0]['result'][0]['code']);
    }

    /**
     * @return array[]
     */
    public function emailsToForce3dsSoftBlocks(): array
    {
        return [
            ['311', 'velocity311@probiller.mindgeek.com'],
            ['312', 'velocity312@probiller.mindgeek.com'],
            ['313', 'velocity313@probiller.mindgeek.com'],
            ['314', 'velocity314@probiller.mindgeek.com'],
            ['315', 'velocity315@probiller.mindgeek.com'],
            ['316', 'velocity316@probiller.mindgeek.com'],
            ['317', 'velocity317@probiller.mindgeek.com'],
        ];
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
     * @return string
     */
    private function payload(): string
    {
        return '{
            "identifier": "velocity311@probiller.mindgeek.com",
            "sessionId": "ba2e12e0-dd84-40a4-b809-1f9ed7ecb216",
            "businessGroupId": "07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1",
            "siteId": "299f9d47-cf3d-11e9-8c91-0cc47a283dd2",
            "event": "ProcessCustomer",
            "data": {
                "totalAmount": [
                    "0.01"
                ],
                "bin": [
                    "222222"
                ],
                "lastFour": [
                    "2224"
                ],
                "firstName": [
                    "firstName"
                ],
                "lastName": [
                    "lastName"
                ],
                "email": [
                    "velocity311@probiller.mindgeek.com"
                ],
                "address": [
                    null
                ],
                "city": [
                    null
                ],
                "state": [
                    null
                ],
                "zipCode": [
                    "H4P2H2"
                ],
                "countryCode": [
                    "CA"
                ],
                "domain": [
                    "probiller.mindgeek.com"
                ]
                 }
                }';
    }

    /**
     * @param string $email
     * @return PurchaseProcessed|MockObject
     */
    private function mockedPurchaseProcess(string $email): PurchaseProcessed
    {
        $mockedPurchaseProcess = $this->createMock(PurchaseProcessed::class);
        $mockedPurchaseProcess->method('toArray')->willReturn(
            [
                'memberInfo'=>[
                    'username' => 'teste',
                    'firstName' => 'firstName',
                    'lastName' => 'lastName',
                    'countryCode' => 'US',
                    'zipCode' => 'HX0HX0',
                    'address' => 'address',
                    'city' => 'city',
                    'email' => $email
                ],
                'payment'=> [
                    'first6' => '123456',
                    'last4' => '1234'
                ],
                'selectedCrossSells' => [],
                'status' => 'declined',
                'initialAmount' => 0.01
            ]
        );
        return $mockedPurchaseProcess;
    }

    /**
     * @param string $email
     * @return array
     * @throws \CommonServices\FraudServiceClient\ApiException
     */
    private function getAdviceFromFraudRecommendation(string $email): array
    {
        $fraudParamRequest = json_decode($this->payload(), true);

        $fraudParamRequest["identifier"] = $email;
        $fraudParamRequest["data"]['email'] = [$email];

        return $this->client->apiV3AdvicePostWithHttpInfo(
            new AdviceRequestDto(
                $fraudParamRequest
            )
        );
    }
}
