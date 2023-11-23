<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\ApiException;
use CommonServices\FraudServiceClient\Configuration;
use CommonServices\FraudServiceClient\Model\AdviceResponseModelResult;
use CommonServices\FraudServiceClient\Model\AdviceResponseModelV3;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\NonPCIPaymentFormData;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\FraudRecommendationClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\FraudRecommendationTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForExistingMemberOnInitTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewCardOnProcessTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewMemberOnInitTranslatingService;
use Tests\IntegrationTestCase;

class FraudRecommendationTranslatingServiceTest extends IntegrationTestCase
{
    /** @var HandlerStack */
    protected $handlerStack;

    protected $client;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $mockedAdviceResult = $this->createMock(AdviceResponseModelResult::class);
        $mockedAdviceResult->method('getSeverity')->willReturn('Allow');
        $mockedAdviceResult->method('getCode')->willReturn('400');
        $mockedAdviceResult->method('getMessage')->willReturn('Allow_Transaction');

        $mockedModel = $this->createMock(AdviceResponseModelV3::class);
        $mockedModel->method('getResult')->willReturn([$mockedAdviceResult,$mockedAdviceResult]);

        $responseArr = [$mockedModel];

        $this->client = $this->createMock(AdviceApi::class);
        $this->client->method('getConfig')->willReturn(
            $this->createMock(Configuration::class)
        );
        $this->client->method('apiV3AdvicePostWithHttpInfo')->willReturn($responseArr);
    }

    /**
     * @test
     * @return FraudRecommendationCollection
     *
     * @throws \Exception
     */
    public function it_should_return_a_correct_fraud_recommendation_for_init_existing_member(): FraudRecommendationCollection
    {
        /** @var RetrieveFraudRecommendationForExistingMemberOnInitTranslatingService | MockObject $service */
        $service = $this->getMockBuilder(RetrieveFraudRecommendationForExistingMemberOnInitTranslatingService::class)
            ->setConstructorArgs(
                [
                    new RetrieveFraudRecommendationAdapter(
                        new FraudRecommendationClient($this->client),
                        new FraudRecommendationTranslator()
                    )
                ]
            )
            ->setMethods(null)
            ->getMock();

        $fraudAdvice = $service->retrieve(
            BusinessGroupId::create(),
            SiteId::create(),
            Ip::create('10.10.10.10'),
            CountryCode::create('ca'),
            Amount::create(0),
            SessionId::create(),
            Email::create('teste@teste.com'),
            []
        );

        $this->assertInstanceOf(FraudRecommendationCollection::class, $fraudAdvice);

        return $fraudAdvice;
    }

    /**
     * @test
     * @depends it_should_return_a_correct_fraud_recommendation_for_init_existing_member
     * @param FraudRecommendationCollection $fraudAdvice
     */
    public function it_should_create_a_fraud_collection_with_bypass_template_true_when_response_code_is_400(
        FraudRecommendationCollection $fraudAdvice
    ): void {
        $this->assertTrue($fraudAdvice->hasBypassPaymentTemplateValidation());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     */
    public function it_should_return_a_correct_fraud_recommendation_for_init_new_member(): void
    {
        /** @var RetrieveFraudRecommendationForNewMemberOnInitTranslatingService | MockObject $service */
        $service = $this->getMockBuilder(RetrieveFraudRecommendationForNewMemberOnInitTranslatingService::class)
            ->setConstructorArgs(
                [
                    new RetrieveFraudRecommendationAdapter(
                        new FraudRecommendationClient($this->client),
                        new FraudRecommendationTranslator()
                    )
                ]
            )
            ->setMethods(null)
            ->getMock();

        $fraudAdvice = $service->retrieve(
            BusinessGroupId::create(),
            SiteId::create(),
            Ip::create('10.10.10.10'),
            CountryCode::create('ca'),
            SessionId::create(),
            []
        );

        $this->assertInstanceOf(FraudRecommendationCollection::class, $fraudAdvice);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidAmountException
     * @throws \Exception
     */
    public function it_should_return_a_correct_fraud_recommendation_for_process_new_member(): void
    {
        /** @var RetrieveFraudRecommendationForNewCardOnProcessTranslatingService | MockObject $service */
        $service = $this->getMockBuilder(RetrieveFraudRecommendationForNewCardOnProcessTranslatingService::class)
            ->setConstructorArgs(
                [
                    new RetrieveFraudRecommendationAdapter(
                        new FraudRecommendationClient($this->client),
                        new FraudRecommendationTranslator()
                    )
                ]
            )
            ->setMethods(null)
            ->getMock();

        $nonPciFormData = NonPCIPaymentFormData::create(
            Bin::createFromString('123456'),
            LastFour::createFromString('1234'),
            FirstName::create('firstName'),
            LastName::create('lastName'),
            Email::create('email@mindgeek.com'),
            Zip::create('zip'),
            CountryCode::create('ca'),
            'street',
            'city',
            'state'
        );


        $fraudAdvice = $service->retrieve(
            BusinessGroupId::create(),
            SiteId::create(),
            $nonPciFormData,
            Amount::create(0),
            SessionId::create(),
            []
        );

        $this->assertInstanceOf(FraudRecommendationCollection::class, $fraudAdvice);
    }

    /**
     * @test
     * @throws Exception
     * @throws FraudAdviceApiException
     */
    public function common_fraud_service_exception_should_not_reflect_on_system()
    {
        $cfsResponse = ['message' => '[500] Client Error!', 'code'=>500];
        $response = new \GuzzleHttp\Psr7\Response(500,[],json_encode($cfsResponse));
        $mockedGuzzle = $this->createMock(Client::class);
        $mockedGuzzle->method('send')->willReturn($response);
        $api = new AdviceApi($mockedGuzzle);
        $adapter = new RetrieveFraudRecommendationAdapter(new FraudRecommendationClient($api), new FraudRecommendationTranslator());

        $fraudCollection = $adapter->retrieve(
            $this->faker->uuid,
            $this->faker->uuid,
            'ProcessCustomer',
            [],
            $this->faker->uuid,
            []
        );
        $this->assertInstanceOf(FraudRecommendationCollection::class, $fraudCollection);
        $this->assertEquals(FraudRecommendation::createDefaultAdvice(), $fraudCollection->first());
        $this->assertCount(1, $fraudCollection->toArray());
    }
}
