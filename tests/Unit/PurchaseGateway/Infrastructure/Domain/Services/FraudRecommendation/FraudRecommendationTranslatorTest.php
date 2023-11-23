<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use CommonServices\FraudServiceClient\Model\AdviceResponseModelResult;
use CommonServices\FraudServiceClient\Model\AdviceResponseModelV3;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\Exception\FraudRecommendationClientException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\FraudRecommendationTranslator as FraudTranslator;
use Tests\UnitTestCase;

class FraudRecommendationTranslatorTest extends UnitTestCase
{
    /**
     * @var FraudTranslator
     */
    private $translator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->translator = new FraudTranslator();
    }

    /**
     * @test
     */
    public function it_should_return_fraud_recommendation_collection()
    {
        $mockedAdviceResult = $this->createMock(AdviceResponseModelResult::class);
        $mockedAdviceResult->method('getSeverity')->willReturn('Block');
        $mockedAdviceResult->method('getCode')->willReturn('200');
        $mockedAdviceResult->method('getMessage')->willReturn('Show_Captcha');

        $mockedModel = $this->createMock(AdviceResponseModelV3::class);
        $mockedModel->method('getResult')->willReturn([$mockedAdviceResult,$mockedAdviceResult]);

        $collection = $this->translator->translate([$mockedModel]);
        $this->assertInstanceOf(FraudRecommendationCollection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertEqualsCanonicalizing(['severity','code','message'], array_keys($collection->first()->toArray()));
        $this->assertEquals(FraudIntegrationMapper::fraudRecommendationCaptchaArray(), $collection->first()->toArray());
    }

    /**
     * @test
     */
    public function it_should_transform_full_response_into_collection()
    {
        $mockedAdviceResult = $this->createMock(AdviceResponseModelResult::class);
        $mockedAdviceResult->method('getSeverity')->willReturn('Block');
        $mockedAdviceResult->method('getCode')->willReturn('200');
        $mockedAdviceResult->method('getMessage')->willReturn('Show_Captcha');

        $mockedModel = $this->createMock(AdviceResponseModelV3::class);
        $mockedModel->method('getResult')->willReturn([$mockedAdviceResult,$mockedAdviceResult]);

        $collection = $this->translator->transformResponseIntoCollection($mockedModel);
        $this->assertCount(2, $collection);

    }

    /**
     * @test
     */
    public function it_should_transform_advice_into_array()
    {
        $mockedAdviceResult = $this->createMock(AdviceResponseModelResult::class);
        $mockedAdviceResult->method('getSeverity')->willReturn('Block');
        $mockedAdviceResult->method('getCode')->willReturn('200');
        $mockedAdviceResult->method('getMessage')->willReturn('Show_Captcha');

        $advice = $this->translator->transformAdviceIntoArray($mockedAdviceResult);
        $this->assertEqualsCanonicalizing(['severity','code','message'], array_keys($advice));
        $this->assertEquals(FraudIntegrationMapper::fraudRecommendationCaptchaArray(), $advice);
    }


    /**
     * @test
     */
    public function it_should_transform_advice_into_array_even_with_null_params()
    {
        $mockedAdviceResult = $this->createMock(AdviceResponseModelResult::class);
        $mockedAdviceResult->method('getSeverity')->willReturn(null);
        $mockedAdviceResult->method('getCode')->willReturn(null);
        $mockedAdviceResult->method('getMessage')->willReturn(null);

        $advice = $this->translator->transformAdviceIntoArray($mockedAdviceResult);
        $this->assertEqualsCanonicalizing(['severity','code','message'], array_keys($advice));

        FraudRecommendation::create($advice['code'], $advice['severity'], $advice['message']);
    }

    /**
     * @test
     * @dataProvider invalid_responses_from_fraud
     * @param $invalidResponseFromFraud
     * @throws Exception
     * @throws FraudRecommendationClientException
     */
    public function response_should_throw_exception_if_not_valid($invalidResponseFromFraud)
    {
        $this->expectException(FraudRecommendationClientException::class);
        $collection = $this->translator->translate($invalidResponseFromFraud);
    }

    /**
     * @return array
     */
    public function invalid_responses_from_fraud()
    {
        return [
            'invalid array' => [['123'=>123]],
        ];
    }

    /**
     * @test
     */
    public function it_should_return_throw_exception_when_result_inside_response_is_empty()
    {
        $this->expectException(FraudRecommendationClientException::class);

        $mockedModel = $this->createMock(AdviceResponseModelV3::class);
        $mockedModel->method('getResult')->willReturn([]);

        $this->translator->translate([$mockedModel]);
    }
}
