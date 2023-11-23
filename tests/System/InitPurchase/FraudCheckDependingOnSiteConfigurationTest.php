<?php

namespace Tests\System\InitPurchase;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;

/**
 * Class FraudCheckDependingOnSiteConfigurationTest
 * @package Tests\System\InitPurchase\FraudCheck
 * @group   common-fraud-service-integration
 */
class FraudCheckDependingOnSiteConfigurationTest extends InitPurchase
{
    /** @var string Force 3DS ip */
    public static $forceThreeDSIp = '5.0.0.0';

    /** @var string 3ds siteId */
    public static $threeDSSiteId = '8e34c94e-135f-4acb-9141-58b3a6e56c74'; //RK

    /** @var string $siteId */
    protected $siteId;

    /** @var string $blacklistedIp */
    protected $blacklistedIp = '1.2.3.4';

    /**Mocked ip updated*/
    protected $blackListMockedIp = '1.2.3.4';

    protected $expectedDefaultFraudAdvice = [
        'captcha'   => false,
        'blacklist' => false
    ];

    protected $expectedInitFraudAdviceForEnabledFraudCheck = [
        'captcha'   => true,
        'blacklist' => false
    ];

    protected $expectedInitFraudAdviceForEnabledFraudCheckWithBlacklist = [
        'captcha'   => false,
        'blacklist' => true
    ];

    protected $forceThreeDSFraudRecommendation = [
        'severity' => FraudIntegrationMapper::BLOCK,
        'code'     => FraudRecommendation::FORCE_THREE_D,
        'message'  => FraudIntegrationMapper::FORCE_THREE_D
    ];

    /**
     * @inheritdoc
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->siteId = $this->payload['siteId'];
    }

    /**
     * @return array
     */
    private function returnExpectedInitFraudAdviceForBlackList(): array
    {
        return [
            'severity' => FraudIntegrationMapper::BLOCK,
            'code'     => FraudRecommendation::BLACKLIST,
            'message'  => FraudIntegrationMapper::BLACKLIST_REQUIRED
        ];
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_correct_fraud_advice_when_fraud_service_is_enabled_and_the_IP_is_blacklisted(): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('the ip used is not on black list anymore');
            // Update payload with blacklisted IP
            $this->payload['clientIp'] = $this->blacklistedIp;
        }

        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules enabled so that the fraud check will return correct fraud advice
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }

        $this->payload['clientIp'] = $this->blackListMockedIp;

        // Send request
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        $responseDecoded = json_decode($this->response->getContent(), true);

        $this->assertEquals(
            $this->expectedInitFraudAdviceForEnabledFraudCheckWithBlacklist,
            $responseDecoded['fraudAdvice']
        );

        if (config('app.feature.common_fraud_enable_for.init.join')) {
            $this->assertEquals(
                $this->returnExpectedInitFraudAdviceForBlackList(),
                $responseDecoded['fraudRecommendation']
            );
        }

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_correct_fraud_recommendation_when_fraud_service_is_enabled_and_the_IP_is_force_three_d(): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('the ip used is not forced 3DS anymore');
            // Update payload with blacklisted IP
            $this->payload['clientIp'] = self::$forceThreeDSIp;
        }

        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules enabled so that the fraud check will return correct fraud advice
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }

        $this->payload['clientIp'] = self::$forceThreeDSIp;
        $this->payload['siteId']   = self::$threeDSSiteId;

        $initHeaders              = $this->header();
        $initHeaders['x-api-key'] = $this->paysitesXApiKey();

        // Send request
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $initHeaders);
        $response->assertResponseStatus(Response::HTTP_OK);

        $responseDecoded = json_decode($this->response->getContent(), true);

        $this->assertEquals(
            $this->forceThreeDSFraudRecommendation,
            $responseDecoded['fraudRecommendation']
        );

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_correct_fraud_advice_when_fraud_service_is_enabled_and_the_IP_is_not_blacklisted(): void
    {
        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules enabled so that the fraud check will return correct fraud advice
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }

        // Send request
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        $responseDecoded = json_decode($this->response->getContent(), true);

        $this->assertEquals($this->expectedDefaultFraudAdvice, $responseDecoded['fraudAdvice']);

        if (config('app.feature.common_fraud_enable_for.init.join')) {
            $this->assertEquals(
                FraudRecommendation::createDefaultAdvice()->toArray(),
                $responseDecoded['fraudRecommendation']
            );
        }

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_skip_fraud_check_and_return_advice_false_when_fraud_service_is_disabled_and_the_IP_is_blacklisted(): void
    {
        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules disabled so that the fraud check will return correct fraud advice
        if ($isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }

        // Update payload with blacklisted IP
        $this->payload['clientIp'] = $this->blacklistedIp;
        if (config('app.feature.common_fraud_enable_for.init.join')) {
            // Update payload with blacklisted IP
            $this->payload['clientIp'] = $this->blackListMockedIp;
        }

        $this->payload['siteId'] = self::TESTING_SITE_NO_FRAUD;

        // Send request
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        $responseDecoded = json_decode($this->response->getContent(), true);

        $this->assertEquals($this->expectedDefaultFraudAdvice, $responseDecoded['fraudAdvice']);

        if (config('app.feature.common_fraud_enable_for.init.join')) {
            $this->assertEquals(
                FraudRecommendation::createDefaultAdvice()->toArray(),
                $responseDecoded['fraudRecommendation']
            );
        }

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if ($isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_skip_fraud_check_and_return_advice_false_when_fraud_service_is_disabled_and_the_IP_is_not_blacklisted(): void
    {
        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules disabled so that the fraud check will return correct fraud advice
        if ($isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, false);
        }

        // Send request
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        $responseDecoded = json_decode($this->response->getContent(), true);

        $this->assertEquals($this->expectedDefaultFraudAdvice, $responseDecoded['fraudAdvice']);

        if (config('app.feature.common_fraud_enable_for.init.join')) {
            $this->assertEquals(
                FraudRecommendation::createDefaultAdvice()->toArray(),
                $responseDecoded['fraudRecommendation']
            );
        }

        // if the fraud was disabled update to initial value so that we dont break the other tests that may use fraud service value
        if ($isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }
    }

    public static function getTriggerThreeDSCardNo()
    {
        return $_ENV['ROCKETGATE_CARD_NUMBER_TRIGGER_3DS'];
    }
}
