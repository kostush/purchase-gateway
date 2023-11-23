<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application;

use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Domain\Model\Force3dsCodes;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use Tests\UnitTestCase;

class FraudIntegrationMapperTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function fraudAdviceProvider(): array
    {
        return [
            'advice captcha on init' => [
                'markFraudAdviceMethod'         => 'markInitCaptchaAdvised',
                'expectedFraudRecommendation' => [
                    'code'     => FraudRecommendation::CAPTCHA,
                    'severity' => FraudIntegrationMapper::BLOCK,
                    'message'  => FraudIntegrationMapper::CAPTCHA_REQUIRED,
                ]
            ],
            'advice captcha on process' => [
                'markFraudAdviceMethod'         => 'markProcessCaptchaAdvised',
                'expectedFraudRecommendation' => [
                    'code'     => FraudRecommendation::CAPTCHA,
                    'severity' => FraudIntegrationMapper::BLOCK,
                    'message'  => FraudIntegrationMapper::CAPTCHA_REQUIRED,
                ]
            ],
            'blacklisted on init' => [
                'markFraudAdviceMethod'         => 'markBlacklistedOnInit',
                'expectedFraudRecommendation' => [
                    'code'     => FraudRecommendation::BLACKLIST,
                    'severity' => FraudIntegrationMapper::BLOCK,
                    'message'  => FraudIntegrationMapper::BLACKLIST_REQUIRED,
                ]
            ],
            'blacklisted on process' => [
                'markFraudAdviceMethod'         => 'markBlacklistedOnProcess',
                'expectedFraudRecommendation' => [
                    'code'     => FraudRecommendation::BLACKLIST,
                    'severity' => FraudIntegrationMapper::BLOCK,
                    'message'  => FraudIntegrationMapper::BLACKLIST_REQUIRED,
                ]
            ],
            'force threeD' => [
                'markFraudAdviceMethod'         => 'markForceThreeDOnInit',
                'expectedFraudRecommendation' => [
                    'code'     => FraudRecommendation::FORCE_THREE_D,
                    'severity' => FraudIntegrationMapper::BLOCK,
                    'message'  => FraudIntegrationMapper::FORCE_THREE_D,
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider fraudAdviceProvider
     * @return void
     */
    public function it_should_return_a_valid_recommendation($markFraudAdviceMethod, $expectedFraudRecommendation): void
    {
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->{$markFraudAdviceMethod}();

        $recommendation = FraudIntegrationMapper::mapFraudAdviceToFraudRecommendation($fraudAdvice);
        $this->assertEquals($expectedFraudRecommendation['code'],     $recommendation->code());
        $this->assertEquals($expectedFraudRecommendation['severity'], $recommendation->severity());
        $this->assertEquals($expectedFraudRecommendation['message'],  $recommendation->message());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_recommendation_when_no_advice(): void
    {
        $fraudAdvice = FraudAdvice::create();

        $recommendation = FraudIntegrationMapper::mapFraudAdviceToFraudRecommendation($fraudAdvice);
        $this->assertEquals(FraudRecommendation::DEFAULT_CODE, $recommendation->code());
        $this->assertEquals(FraudRecommendation::DEFAULT_SEVERITY, $recommendation->severity());
        $this->assertEquals(FraudRecommendation::DEFAULT_MESSAGE,  $recommendation->message());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_black_list_when_there_are_captcha_and_black(): void
    {
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markBlacklistedOnInit();
        $fraudAdvice->markProcessCaptchaAdvised();

        $recommendation = FraudIntegrationMapper::mapFraudAdviceToFraudRecommendation($fraudAdvice);
        $this->assertEquals(FraudRecommendation::BLACKLIST, $recommendation->code());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_advice_when_recommendation_captch_on_init(): void
    {
        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnInit(
            new FraudRecommendationCollection([FraudRecommendation::create(
                FraudRecommendation::CAPTCHA,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::CAPTCHA_REQUIRED
            )])
        );

        $this->assertTrue($fraudAdvice->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_advice_when_recommendation_blacklisted_on_init(): void
    {
        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnInit(
            new FraudRecommendationCollection([FraudRecommendation::create(
                FraudRecommendation::BLACKLIST,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::BLACKLIST_REQUIRED
            )])
        );

        $this->assertTrue($fraudAdvice->isBlacklistedOnInit());
    }

    /**
     * @test
     * @param int $ThreeDsSoftBlockCode
     * @return void
     * @dataProvider threeDsCodes
     */
    public function it_should_return_a_valid_fraud_advice_when_recommendation_force_three_ds_on_init(int $ThreeDsSoftBlockCode): void
    {
        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnInit(
            new FraudRecommendationCollection([FraudRecommendation::create(
                $ThreeDsSoftBlockCode,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::FORCE_THREE_D
            )])
        );

        $this->assertTrue($fraudAdvice->isForceThreeD());
    }

    /**
     * @return array
     */
    public function threeDsCodes(): array
    {
        $force3dsDefault                       = new Force3dsCodes(Force3dsCodes::FORCE_3DS);
        $declineCountVelocity                  = new Force3dsCodes(Force3dsCodes::FORCE_3DS_DECLINE_COUNT_VELOCITY);
        $force_3ds_nonrecurring_count_velocity = new Force3dsCodes(Force3dsCodes::FORCE_3DS_NONRECURRING_COUNT_VELOCITY);
        $force_3ds_sign_up_allowance_velocity  = new Force3dsCodes(Force3dsCodes::FORCE_3DS_SIGN_UP_ALLOWANCE_VELOCITY);
        $force_3ds_name_count_velocity         = new Force3dsCodes(Force3dsCodes::FORCE_3DS_NAME_COUNT_VELOCITY);
        $force_3ds_creditcard_count_velocity   = new Force3dsCodes(Force3dsCodes::FORCE_3DS_CREDITCARD_COUNT_VELOCITY);
        $force_3ds_uniqueip_count_velocity     = new Force3dsCodes(Force3dsCodes::FORCE_3DS_UNIQUEIP_COUNT_VELOCITY);
        $force_3ds_zip_count_velocity          = new Force3dsCodes(Force3dsCodes::FORCE_3DS_ZIP_COUNT_VELOCITY);

        return [
            [$force3dsDefault->getKey() => $force3dsDefault->getValue()],
            [$declineCountVelocity->getKey() => $declineCountVelocity->getValue()],
            [$force_3ds_nonrecurring_count_velocity->getKey() => $force_3ds_nonrecurring_count_velocity->getValue()],
            [$force_3ds_sign_up_allowance_velocity->getKey() => $force_3ds_sign_up_allowance_velocity->getValue()],
            [$force_3ds_name_count_velocity->getKey() => $force_3ds_name_count_velocity->getValue()],
            [$force_3ds_creditcard_count_velocity->getKey() => $force_3ds_creditcard_count_velocity->getValue()],
            [$force_3ds_uniqueip_count_velocity->getKey() => $force_3ds_uniqueip_count_velocity->getValue()],
            [$force_3ds_zip_count_velocity->getKey() => $force_3ds_zip_count_velocity->getValue()]
        ];
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_advice_when_recommendation_captch_on_process(): void
    {
        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
            new FraudRecommendationCollection([FraudRecommendation::create(
                FraudRecommendation::CAPTCHA,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::CAPTCHA_REQUIRED
            )]),
            FraudAdvice::create()
        );

        $this->assertTrue($fraudAdvice->isProcessCaptchaAdvised());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_advice_when_recommendation_blacklisted_on_process(): void
    {
        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
            new FraudRecommendationCollection([FraudRecommendation::create(
                FraudRecommendation::BLACKLIST,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::BLACKLIST_REQUIRED
            )]),
            FraudAdvice::create()
        );

        $this->assertTrue($fraudAdvice->isBlacklistedOnProcess());
    }

    /**
     * @test
     * @param int $threeDsSoftBlockCode
     * @return void
     * @dataProvider threeDsCodes
     */
    public function it_should_return_a_valid_fraud_advice_when_recommendation_force_three_ds_on_process(int $threeDsSoftBlockCode): void
    {
        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
            new FraudRecommendationCollection([FraudRecommendation::create(
                $threeDsSoftBlockCode,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::FORCE_THREE_D
            )]),
            FraudAdvice::create()
        );

        $this->assertTrue($fraudAdvice->isForceThreeD());
    }


    /**
     * @test
     * @return void
     */
    public function it_should_false_previous_advices_and_keep_timesBlacklisted_on_new_fraud_recommendation_on_process(): void
    {
        // GIVEN
        $previousFraudAdvice = FraudAdvice::create();
        $previousFraudAdvice->markBlacklistedOnProcess();
        $previousFraudAdvice->increaseTimesBlacklisted();

        // WHEN
        $newFraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
            new FraudRecommendationCollection([FraudRecommendation::create(
                FraudRecommendation::CAPTCHA,
                FraudIntegrationMapper::BLOCK,
                FraudIntegrationMapper::CAPTCHA_REQUIRED
            )]),
            $previousFraudAdvice
        );

        // THEN
        $this->assertTrue($newFraudAdvice->isProcessCaptchaAdvised());

        $this->assertFalse($newFraudAdvice->isBlacklistedOnProcess());
        $this->assertEquals(1, $newFraudAdvice->timesBlacklisted());

        $this->assertFalse($newFraudAdvice->isForceThreeD());
    }


    /**
     * @test
     * @return void
     */
    public function it_should_clone_fraud_advice(): void
    {
        $previousFraudAdvice = FraudAdvice::create();
        $previousFraudAdvice->markBlacklistedOnProcess();
        $previousFraudAdvice->increaseTimesBlacklisted();
        $previousFraudAdvice->markDetectThreeDUsage();

        $newFraudAdvice = clone $previousFraudAdvice;

        $this->assertEquals((string) $previousFraudAdvice->ip(), (string) $newFraudAdvice->ip());
        $this->assertEquals((string) $previousFraudAdvice->email(), (string) $newFraudAdvice->email());
        $this->assertEquals((string) $previousFraudAdvice->zip(), (string) $newFraudAdvice->zip());
        $this->assertEquals((string) $previousFraudAdvice->bin(), (string) $newFraudAdvice->bin());
        $this->assertEquals($previousFraudAdvice->isInitCaptchaAdvised(), $newFraudAdvice->isInitCaptchaAdvised());
        $this->assertEquals($previousFraudAdvice->isInitCaptchaValidated(), $newFraudAdvice->isInitCaptchaValidated());
        $this->assertEquals($previousFraudAdvice->isProcessCaptchaAdvised(), $newFraudAdvice->isProcessCaptchaAdvised());
        $this->assertEquals($previousFraudAdvice->isProcessCaptchaValidated(), $newFraudAdvice->isProcessCaptchaValidated());
        $this->assertEquals($previousFraudAdvice->isBlacklistedOnInit(), $newFraudAdvice->isBlacklistedOnInit());
        $this->assertEquals($previousFraudAdvice->isBlacklistedOnProcess(), $newFraudAdvice->isBlacklistedOnProcess());
        $this->assertEquals($previousFraudAdvice->isCaptchaAlreadyValidated(), $newFraudAdvice->isCaptchaAlreadyValidated());
        $this->assertEquals($previousFraudAdvice->timesBlacklisted(), $newFraudAdvice->timesBlacklisted());
        $this->assertEquals($previousFraudAdvice->isForceThreeD(), $newFraudAdvice->isForceThreeD());
        $this->assertEquals($previousFraudAdvice->isDetectThreeDUsage(), $newFraudAdvice->isDetectThreeDUsage());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_recommendation_array_map_blacklist_init(): void
    {
        $fraudRecommendationArray = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            [
                'blacklistedOnInit'     => true,
                'blacklistedOnProcess'  => false,
                'initCaptchaAdvised'    => false,
                'processCaptchaAdvised' => false,
                'forceThreeD'           => false,
            ]
        );

        $this->assertSame(FraudRecommendation::BLACKLIST, $fraudRecommendationArray['code']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_recommendation_array_map_blacklist_process(): void
    {
        $fraudRecommendationArray = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            [
                'blacklistedOnInit'     => false,
                'blacklistedOnProcess'  => true,
                'initCaptchaAdvised'    => false,
                'processCaptchaAdvised' => false,
                'forceThreeD'           => false,
            ]
        );

        $this->assertSame(FraudRecommendation::BLACKLIST, $fraudRecommendationArray['code']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_recommendation_array_map_captcha_init(): void
    {
        $fraudRecommendationArray = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            [
                'blacklistedOnInit'     => false,
                'blacklistedOnProcess'  => false,
                'initCaptchaAdvised'    => true,
                'processCaptchaAdvised' => false,
                'forceThreeD'           => false,
            ]
        );

        $this->assertSame(FraudRecommendation::CAPTCHA, $fraudRecommendationArray['code']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_recommendation_array_map_captcha_process(): void
    {
        $fraudRecommendationArray = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            [
                'blacklistedOnInit'     => false,
                'blacklistedOnProcess'  => false,
                'initCaptchaAdvised'    => false,
                'processCaptchaAdvised' => true,
                'forceThreeD'           => false,
            ]
        );

        $this->assertSame(FraudRecommendation::CAPTCHA, $fraudRecommendationArray['code']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_recommendation_array_map_force_three_ds(): void
    {
        $fraudRecommendationArray = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            [
                'blacklistedOnInit'     => false,
                'blacklistedOnProcess'  => false,
                'initCaptchaAdvised'    => false,
                'processCaptchaAdvised' => false,
                'forceThreeD'           => true,
            ]
        );

        $this->assertSame(FraudRecommendation::FORCE_THREE_D, $fraudRecommendationArray['code']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_fraud_recommendation_array_map_default(): void
    {
        $fraudRecommendationArray = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            [
                'blacklistedOnInit'     => false,
                'blacklistedOnProcess'  => false,
                'initCaptchaAdvised'    => false,
                'processCaptchaAdvised' => false,
                'forceThreeD'           => false,
            ]
        );

        $this->assertSame(
            FraudRecommendation::createDefaultAdvice()->toArray()['code'],
            $fraudRecommendationArray['code']
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_default_recommendation_on_new_default_advice_and_previous_blocked_advice(): void
    {
        $previousFraudAdvice = FraudAdvice::create();
        $previousFraudAdvice->markBlacklistedOnProcess();

        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
            new FraudRecommendationCollection([FraudRecommendation::createDefaultAdvice()]),
            $previousFraudAdvice
        );

        $this->assertFalse($fraudAdvice->isForceThreeD());
        $this->assertFalse($fraudAdvice->isInitCaptchaAdvised());
        $this->assertFalse($fraudAdvice->isBlacklistedOnProcess());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     */
    public function it_should_return_default_recommendation_on_new_default_advice_and_previous_captcha_advice(): void
    {
        $previousFraudAdvice = FraudAdvice::create();
        $previousFraudAdvice->markProcessCaptchaAdvised();
        $previousFraudAdvice->validateProcessCaptcha();

        $fraudAdvice = FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
            new FraudRecommendationCollection([FraudRecommendation::createDefaultAdvice()]),
            $previousFraudAdvice
        );

        $this->assertTrue($fraudAdvice->isProcessCaptchaValidated());
    }
}
