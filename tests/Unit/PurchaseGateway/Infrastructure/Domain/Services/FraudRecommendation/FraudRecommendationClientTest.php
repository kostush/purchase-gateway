<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\ApiException;
use CommonServices\FraudServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\FraudRecommendationClient;
use Tests\UnitTestCase;

class FraudRecommendationClientTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws FraudAdviceCsCodeApiException
     */
    public function it_should_return_empty_on_invalid_json()
    {
        $confMock = $this->createMock(Configuration::class);

        $adviceApi = $this->createMock(AdviceApi::class);
        $adviceApi->method('getConfig')->willReturn($confMock);
        $adviceApi->method('apiV3AdvicePostWithHttpInfo')->willThrowException(new ApiException);

        $client = new FraudRecommendationClient($adviceApi);

        $response = $client->retrieve(
            'businessGroupId',
            'site',
            'event',
            [],
            'sessionId',
            []
        );
        $this->assertEmpty($response);
    }
}
