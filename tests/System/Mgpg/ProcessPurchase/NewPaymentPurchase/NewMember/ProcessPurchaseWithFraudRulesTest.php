<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithFraudRulesTest extends ProcessPurchaseBase
{
    /** @var ConfigService */
    protected $configService;

    /** @var Site|null */
    protected $site;

    /**
     * @var bool
     */
    protected $useFraud;

    /**
     * @var string
     */
    protected $blackListEmail = 'blacklisted@test.mindgeek.com';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->configService = $this->app->make(ConfigService::class);
        $this->site          = $this->configService->getSite(
            $this->processPurchasePayloadWithNoSelectedCrossSale()['siteId']
        );

        $this->useFraud = $this->site->services()['fraud']->enabled();
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token(): array
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(true);
        $response->seeHeader('X-Auth-Token');

        return [
            'header'          => (string) $this->response->headers->get('X-Auth-Token'),
            'responseContent' => json_decode($this->response->getContent(), true)
        ];
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     *
     * @param array $params Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_with_fraud_enabled_and_email_which_is_in_fraudlist_should_return_success($params): array
    {
        if (!$this->useFraud) {
            $this->markTestSkipped(
                'The Fraud is disabled for this site'
            );
        }

        $processPurchasePayload                    = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPurchasePayload['member']['email'] = $this->blackListEmail;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($params['header'])
        );
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     * @group common-fraud-service-integration
     */
    public function use_valid_email_on_second_attempt_after_blacklisted_email_should_return_success(): void
    {
        $this->markTestSkipped("This test became pointless because we are not using an email to test it.");
        if (!config('app.feature.common_fraud_enable_for.process.new_credit_card')) {
            $this->markTestSkipped('Common services fraud not enabled.');
            return;
        }

        $response = $this->initPurchaseProcessWithOneCrossSale(true);
        $response->seeHeader('X-Auth-Token');

        $params = [
            'header'          => (string) $this->response->headers->get('X-Auth-Token'),
            'responseContent' => json_decode($this->response->getContent(), true)
        ];

        if (!$this->useFraud) {
            $this->markTestSkipped(
                'The Fraud is disabled for this site'
            );
        }

        /**
         * Attempt 1 - with blacklisted email
         */
        $this->override([
            "fraudService"=> [
                "callProcessCustomer"=> [
                    [
                        "severity" => "Block",
                        "code"     => 100,
                        "message"  => "Blacklist"
                    ]
                ]
            ]
        ]);

        $processPurchasePayload                    = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPurchasePayload['member']['email'] = $this->blackListEmail;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($params['header'])
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $return = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('fraudRecommendation', $return, 'Email has not triggering fraud recommendation.');
        $firstFraudRecommendation = $return['fraudRecommendation'];

        $this->assertEquals(FraudRecommendation::BLACKLIST, $firstFraudRecommendation['code']);

        /**
         * Attempt 2 - with clean email
         */
        $processPurchasePayload['member']['email']       = $this->faker->email;
        $processPurchasePayload['member']['countryCode'] = 'BR';

        $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($params['header'])
        );

        $return = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('fraudRecommendation', $return);
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     *
     * @param array $response Response.
     *
     * @return array
     */
    public function process_purchase_with_fraud_enabled_and_email_which_is_in_fraudlist_should_return_fraud_advice(
        array $response
    ): array {
        $this->assertArrayHasKey('fraudAdvice', $response['responseContent']);

        return $response;
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     * @param array $response Response.
     *
     * @return void
     */
    public function fraud_advice_should_contain_captcha_key(array $response): void
    {
        $this->assertArrayHasKey('captcha', $response['responseContent']['fraudAdvice']);
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     * @param array $response Response.
     *
     * @return void
     */
    public function fraud_advice_should_contain_captcha_with_bool_value(array $response): void
    {
        $this->assertIsBool($response['responseContent']['fraudAdvice']['captcha']);
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     * @param array $response Response
     *
     * @return void
     */
    public function fraud_advice_should_contain_blacklist_key(array $response): void
    {
        $this->assertArrayHasKey('blacklist', $response['responseContent']['fraudAdvice']);
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     * @param array $response Response
     *
     * @return void
     */
    public function fraud_recommendation_should_contain_severity_key(array $response): void
    {
        $this->assertArrayHasKey('severity', $response['responseContent']['fraudRecommendation']);
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     * @param array $response Response
     *
     * @return void
     */
    public function fraud_recommendation_should_contain_code_key(array $response): void
    {
        $this->assertArrayHasKey('code', $response['responseContent']['fraudRecommendation']);
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_with_fraud_rules_should_contain_x_auth_token
     * @param array $response Response
     *
     * @return void
     */
    public function fraud_recommendation_should_contain_message_key(array $response): void
    {
        $this->assertArrayHasKey('message', $response['responseContent']['fraudRecommendation']);
    }
}
