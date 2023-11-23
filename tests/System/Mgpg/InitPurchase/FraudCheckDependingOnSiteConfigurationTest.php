<?php

namespace Tests\System\Mgpg\InitPurchase;

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
    protected $blacklistedIp = '69.42.58.24';

    /**Mocked ip updated*/
    protected $blackListMockedIp = '69.42.58.24';

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
            'message'  => 'Blacklist' //MGPG uses `Blacklist` instead of `Blacklist_Customer`, the adaptor doesn't change that.
        ];
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_correct_fraud_when_fraud_service_black_lists_request(): void
    {
        $isFraudServiceEnabled = $this->isFraudServiceEnabled($this->siteId);

        // for this test we need to have the fraud rules enabled so that the fraud check will return correct fraud advice
        if (!$isFraudServiceEnabled) {
            $this->updateFraudServiceStatus($this->siteId, true);
        }

        $this->override([
            "fraudService"=> [
                "callInitVisitor"=> [
                    [
                        "severity" => "Block",
                        "code"     => 100,
                        "message"  => "Blacklist"
                    ]
                ]
            ]
        ]);

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
        $this->markTestIncomplete(
            // TODO See here: https://wiki.mgcorp.co/display/PI/nextAction+API+Reference
            'MGPG does not do Force 3D logic on init.'
        );

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
        $initHeaders['x-api-key'] = $_ENV['PAYSITES_API_KEY'];

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
        /**
         * //TODO[PZ] On a future version of MGPG it might be possible to override specific settings on a per-request basis.
         * For example to disable the fraud-service in this case. As of right now this is not a possibility.
         */
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'Currently no support to disable MGPG fraud-service.'
        );

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
