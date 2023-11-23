<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Force3dsCodes;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\UnitTestCase;

class FraudRecommendationTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_reset_to_default_values_when_reset_to_default_method_is_called(): void
    {
        $fraudRecommendation = FraudRecommendation::create(
            FraudRecommendation::FORCE_THREE_D,
            FraudRecommendation::DEFAULT_SEVERITY,
            FraudRecommendation::DEFAULT_MESSAGE
        );

        $fraudRecommendation->resetToDefaultIfThreeDForced();

        $this->assertSame(FraudRecommendation::DEFAULT_CODE, $fraudRecommendation->code());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_accept_empty_values(): void
    {
        $fraudRecommendation = FraudRecommendation::create(0, '', '');
        $this->assertInstanceOf(FraudRecommendation::class, $fraudRecommendation);
    }

    /**
     * @test
     */
    public function fraud_recommendation_should_be_not_hard_blocked_for_captcha(): void
    {
        $fraudRecommendation = FraudRecommendation::create(200, FraudRecommendation::BLOCK, 'captcha');
        $this->assertFalse($fraudRecommendation->isHardBlock());
    }

    /**
     * @test
     */
    public function fraud_recommendation_should_be_not_hard_blocked_for_three_ds(): void
    {
        $fraudRecommendation = FraudRecommendation::create(300, FraudRecommendation::BLOCK, '3ds');
        $this->assertFalse($fraudRecommendation->isHardBlock());
    }

    /**
     * @test
     */
    public function fraud_recommendation_should_not_be_hard_blocked_when_create_captcha_advice(): void
    {
        $fraudRecommendation = FraudRecommendation::createCaptchaAdvice();
        $this->assertFalse($fraudRecommendation->isHardBlock());
    }

    /**
     * @test
     */
    public function fraud_recommendation_should_be_soft_blocked_when_create_captcha_advice(): void
    {
        $fraudRecommendation = FraudRecommendation::createCaptchaAdvice();
        $this->assertTrue($fraudRecommendation->isSoftBlock());
    }

    /**
     * @return array
     */
    public function fraudRecommendationHardBlockCodeProvider(): array
    {
        return array(
            array(400),
            array(500),
            array(100),
            array(301),
            array(302),
            array(303),
            array(304),
            array(305),
            array(306),
            array(307),
            array(1)
        );
    }

    /**
     * @dataProvider fraudRecommendationHardBlockCodeProvider
     * @test
     * @param int $code
     */
    public function fraud_recommendation_should_hard_block_for_any_kind_of_code_different_from_captcha_or_soft_3ds_and_severity_block(int $code): void
    {
        $fraudRecommendation = FraudRecommendation::create($code, FraudRecommendation::BLOCK, 'nonCaptcha');
        $this->assertTrue($fraudRecommendation->isHardBlock());
    }

    /**
     * @dataProvider fraudRecommendationHardBlockCodeProvider
     * @test
     * @param int $code
     */
    public function fraud_recommendation_should_not_hard_block_when_severity_is_not_block(int $code): void
    {
        $fraudRecommendation = FraudRecommendation::create($code, 'anotherSeverity', 'nonCaptcha');
        $this->assertFalse($fraudRecommendation->isHardBlock());
    }

    /**
     * @test
     */
    public function fraud_recommendation_is_block_with_severity_block(): void
    {
        $fraudRecommendation = FraudRecommendation::create(100, 'block', 'nonCaptcha');
        $this->assertTrue($fraudRecommendation->isSeverityBlock());
    }

    /**
     * @test
     */
    public function fraud_recommendation_is_soft_block_when_is_captcha(): void
    {
        $fraudRecommendation = FraudRecommendation::create(200, 'block', 'Captcha');
        $this->assertTrue($fraudRecommendation->isSoftBlock());
    }

    /**
     * @test
     * @dataProvider threeDsCodes
     * @param int $code
     */
    public function fraud_recommendation_is_soft_block_when_it_is_in_3ds_soft_block_list(int $code): void
    {
        $fraudRecommendation = FraudRecommendation::create($code, 'block', '3ds');
        $this->assertTrue($fraudRecommendation->isSoftBlock());
    }

    /**
     * @test
     */
    public function fraud_recommendation_is_default_should_be_true_if_created_with_createDefaultAdvice() : void
    {
        $fraudRecommendation = FraudRecommendation::createDefaultAdvice();
        $this->assertTrue($fraudRecommendation->isDefault());
    }

    /**
     * @test
     */
    public function fraud_recommendation_is_default_should_not_be_true_if_created_with_create() : void
    {
        $fraudRecommendation = FraudRecommendation::create(FraudRecommendation::DEFAULT_CODE, FraudRecommendation::DEFAULT_SEVERITY, FraudRecommendation::DEFAULT_MESSAGE);
        $this->assertFalse($fraudRecommendation->isDefault());
    }

    /**
     *
     * @return array
     */
    public function threeDsCodes(): array
    {
        $force3dsDefault                   = new Force3dsCodes(Force3dsCodes::FORCE_3DS);
        $declineCountVelocity              = new Force3dsCodes(Force3dsCodes::FORCE_3DS_DECLINE_COUNT_VELOCITY);
        $force3dsNonrecurringCountVelocity = new Force3dsCodes(Force3dsCodes::FORCE_3DS_NONRECURRING_COUNT_VELOCITY);
        $force3dsSignUpAllowanceVelocity   = new Force3dsCodes(Force3dsCodes::FORCE_3DS_SIGN_UP_ALLOWANCE_VELOCITY);
        $force3dsNameCountVelocity         = new Force3dsCodes(Force3dsCodes::FORCE_3DS_NAME_COUNT_VELOCITY);
        $force3dsCreditcardCountVelocity   = new Force3dsCodes(Force3dsCodes::FORCE_3DS_CREDITCARD_COUNT_VELOCITY);
        $force3dsUniqueipCountVelocity     = new Force3dsCodes(Force3dsCodes::FORCE_3DS_UNIQUEIP_COUNT_VELOCITY);
        $force3dsZipCountVelocity          = new Force3dsCodes(Force3dsCodes::FORCE_3DS_ZIP_COUNT_VELOCITY);

        return [
            [$force3dsDefault->getKey() => $force3dsDefault->getValue()],
            [$declineCountVelocity->getKey() => $declineCountVelocity->getValue()],
            [$force3dsNonrecurringCountVelocity->getKey() => $force3dsNonrecurringCountVelocity->getValue()],
            [$force3dsSignUpAllowanceVelocity->getKey() => $force3dsSignUpAllowanceVelocity->getValue()],
            [$force3dsNameCountVelocity->getKey() => $force3dsNameCountVelocity->getValue()],
            [$force3dsCreditcardCountVelocity->getKey() => $force3dsCreditcardCountVelocity->getValue()],
            [$force3dsUniqueipCountVelocity->getKey() => $force3dsUniqueipCountVelocity->getValue()],
            [$force3dsZipCountVelocity->getKey() => $force3dsZipCountVelocity->getValue()]
        ];
    }
}
