<?php

namespace PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudRecommendationHelper;
use Tests\UnitTestCase;

class FraudRecommendationHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_should_keep_list_same_as_before_when_payment_type_is_cc()
    {
        $list                     = [
            [
                "severity" => "Block",
                "code"     => 200,
                "message"  => "Show_Captcha"
            ],
            [
                "severity" => "Block",
                "code"     => 300,
                "message"  => "Force_3DS"
            ]
        ];
        $recommendationCollection = FraudRecommendationCollection::createFromArray($list);

        $result = FraudRecommendationHelper::filterFraudRecommendationByPaymentType(
            $recommendationCollection,
            CCPaymentInfo::PAYMENT_TYPE
        );

        $this->assertEquals(2, $result->count());
        $this->assertSame($recommendationCollection, $result);
    }

    /**
     * @test
     */
    public function it_should_removes_3ds_recommendation_when_payment_type_is_ach()
    {
        $list                     = [
            [
                "severity" => "Block",
                "code"     => 200,
                "message"  => "Show_Captcha"
            ],
            [
                "severity" => "Block",
                "code"     => 300,
                "message"  => "Force_3DS"
            ]
        ];
        $recommendationCollection = FraudRecommendationCollection::createFromArray($list);

        $result = FraudRecommendationHelper::filterFraudRecommendationByPaymentType(
            $recommendationCollection,
            ChequePaymentInfo::PAYMENT_TYPE
        );

        $this->assertEquals(1, $result->count());
        $this->assertEquals(200, $result->first()->code());
        $this->assertEquals('Show_Captcha', $result->first()->message());
    }

    /**
     * @test
     */
    public function it_should_removes_3ds_recommendation_and_change_the_default_to_captcha_when_payment_type_is_ach()
    {
        $defaultRecommendation = FraudRecommendation::createDefaultAdvice();
        $threeDSRecommendation = FraudRecommendation::create(300, 'block', 'Force_3DS');
        $recommendationCollection = new FraudRecommendationCollection([$defaultRecommendation, $threeDSRecommendation]);

        $result = FraudRecommendationHelper::filterFraudRecommendationByPaymentType(
            $recommendationCollection,
            ChequePaymentInfo::PAYMENT_TYPE
        );

        $this->assertEquals(1, $result->count());
        $this->assertEquals(200, $result->first()->code());
        $this->assertEquals('Show_Captcha', $result->first()->message());
    }

    /**
     * @test
     */
    public function it_should_return_default_recommendation_when_only_3ds_recommendation_is_passed_for_ach()
    {
        $list                     = [
            [
                "severity" => "Block",
                "code"     => 300,
                "message"  => "Force_3DS"
            ]
        ];

        $recommendationCollection = FraudRecommendationCollection::createFromArray($list);

        $result = FraudRecommendationHelper::filterFraudRecommendationByPaymentType(
            $recommendationCollection,
            ChequePaymentInfo::PAYMENT_TYPE
        );

        $this->assertEquals(1, $result->count());
        $this->assertEquals(FraudRecommendation::CAPTCHA, $result->first()->code());
        $this->assertEquals(FraudRecommendation::CAPTCHA_MESSAGE, $result->first()->message());
    }

    /**
     * @test
     */
    public function it_should_change_to_captcha_when_recommendation_is_default_for_ach()
    {
        $recommendationCollection = new FraudRecommendationCollection([FraudRecommendation::createDefaultAdvice()]);
        $result = FraudRecommendationHelper::defineDefaultFraudRecommendationByPaymentType(ChequePaymentInfo::PAYMENT_TYPE, $recommendationCollection);

        $this->assertEquals(1, $result->count());
        $this->assertEquals(FraudRecommendation::CAPTCHA, $result->first()->code());
        $this->assertEquals(FraudRecommendation::CAPTCHA_MESSAGE, $result->first()->message());
        $this->assertEquals(FraudRecommendation::CAPTCHA_SEVERITY, $result->first()->severity());
    }

    /**
     * @test
     */
    public function it_should_not_change_to_captcha_when_recommendation_is_not_default_for_ach()
    {
        $fraudRecommendation = FraudRecommendation::create(FraudRecommendation::DEFAULT_CODE, FraudRecommendation::DEFAULT_SEVERITY, FraudRecommendation::DEFAULT_MESSAGE);
        $recommendationCollection = new FraudRecommendationCollection([$fraudRecommendation]);
        $result = FraudRecommendationHelper::defineDefaultFraudRecommendationByPaymentType(ChequePaymentInfo::PAYMENT_TYPE, $recommendationCollection);

        $this->assertEquals(1, $result->count());
        $this->assertEquals(FraudRecommendation::DEFAULT_CODE, $result->first()->code());
        $this->assertEquals(FraudRecommendation::DEFAULT_MESSAGE, $result->first()->message());
        $this->assertEquals(FraudRecommendation::DEFAULT_SEVERITY, $result->first()->severity());
    }
    
    /**
     * @test
     */
    public function it_should_not_change_to_captcha_when_recommendation_is_default_for_cc()
    {
        $recommendationCollection = new FraudRecommendationCollection([FraudRecommendation::createDefaultAdvice()]);
        $result = FraudRecommendationHelper::defineDefaultFraudRecommendationByPaymentType(CCPaymentInfo::PAYMENT_TYPE, $recommendationCollection);

        $this->assertEquals(1, $result->count());
        $this->assertEquals(FraudRecommendation::DEFAULT_CODE, $result->first()->code());
        $this->assertEquals(FraudRecommendation::DEFAULT_MESSAGE, $result->first()->message());
        $this->assertEquals(FraudRecommendation::DEFAULT_SEVERITY, $result->first()->severity());
    }

    /**
     * @test
     */
    public function it_should_set_default_fraud_recommendation_as_captcha_when_payment_is_ach() 
    {
        $chequePaymentInfo = ChequePaymentInfo::create("1234", "1234", false, "1234", ChequePaymentInfo::PAYMENT_TYPE, ChequePaymentInfo::PAYMENT_METHOD);
        $purchaseProcess = $this->createNewPurchaseProcess($chequePaymentInfo);

        FraudRecommendationHelper::setDefaultFraudRecommendationOnInit($purchaseProcess);

        $this->assertTrue($purchaseProcess->fraudAdvice()->isInitCaptchaAdvised());
        $this->assertEquals(FraudRecommendation::CAPTCHA, $purchaseProcess->fraudRecommendationCollection()->first()->code());
    }

    /**
     * @test
     */
    public function it_should_set_default_fraud_recommendation_when_payment_is_cc()
    {
        $ccPaymentInfo = NewCCPaymentInfo::create("123456", "000", "10", "2100", null);
        $purchaseProcess = $this->createNewPurchaseProcess($ccPaymentInfo);

        FraudRecommendationHelper::setDefaultFraudRecommendationOnInit($purchaseProcess);

        $this->assertFalse($purchaseProcess->fraudAdvice()->isInitCaptchaAdvised());
        $this->assertEquals(FraudRecommendation::DEFAULT_CODE, $purchaseProcess->fraudRecommendationCollection()->first()->code());
    }

    /**
     * @param PaymentInfo $paymentInfo
     * 
     * @return PurchaseProcess
     */
    private function createNewPurchaseProcess(PaymentInfo $paymentInfo): PurchaseProcess
    {
        $purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $paymentInfo,
            new InitializedItemCollection(),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            null
        );
        return $purchaseProcess;
    }

}