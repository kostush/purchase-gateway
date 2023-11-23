<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\ApiException;
use CommonServices\FraudServiceClient\Configuration as FraudServiceCsConfiguration;
use CommonServices\FraudServiceClient\Model\AdviceRequestDto;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use ProBillerNG\EventIngestion\Infrastructure\AzureActiveDirectoryAccessToken as AzureToken;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\EventIngestion\Domain\Exception\FailedCommunicationWithAzureService;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AzureActiveDirectoryAccessToken;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

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
     * @var string
     */
    protected $force3DsEmail = 'velocity314@probiller.mindgeek.com';

    /**
     * @return void
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     * @group common-fraud-service-integration
     */
    public function use_valid_email_on_second_attempt_after_blacklisted_email_should_return_success(): void
    {
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

        if (!empty($return['fraudRecommendation'])) {
            $firstFraudRecommendation = $return['fraudRecommendation'];
            $this->assertEquals(FraudRecommendation::BLACKLIST, $firstFraudRecommendation['code']);
        }

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

    /**
     * @param string $email Email
     * @return bool
     * @throws ApiException
     * @throws FailedCommunicationWithAzureService
     */
    public function emailConfiguredInFraudRecommendationShouldReturnForce3ds(string $email): bool
    {
        $force3dsEndingCode = 31;
        $client             = $this->FraudRecommendationClient();

        $fraudParamRequest = json_decode($this->getFraudAdvicePayload(), true);

        $fraudParamRequest["identifier"]    = $email;
        $fraudParamRequest["data"]['email'] = [$email];

        $response = $client->apiV3AdvicePostWithHttpInfo(
            new AdviceRequestDto(
                $fraudParamRequest
            )
        );

        return $force3dsEndingCode == substr(((string) ($response[0]['result'][0]['code'])), 0, 2);
    }

    /**
     * @return AdviceApi
     * @throws \ProBillerNG\Logger\Exception
     */
    private function FraudRecommendationClient(): AdviceApi
    {
        $client = new AdviceApi(
            new Client(
                [
                    RequestOptions::CONNECT_TIMEOUT => 10,
                    RequestOptions::TIMEOUT         => 10,
                ]
            ),
            (new FraudServiceCsConfiguration())
                ->setApiKeyPrefix('Authorization', 'Bearer')
                ->setHost(env('FRAUD_SERVICE_CS_HOST'))
        );


        $azureADToken = new AzureActiveDirectoryAccessToken(
            config('clientapis.fraudServiceCs.aadAuth.clientId'),
            config('clientapis.fraudServiceCs.aadAuth.tenant')
        );

        $token = $azureADToken->getToken(
            config('clientapis.fraudServiceCs.aadAuth.clientSecret'),
            config('clientapis.fraudServiceCs.aadAuth.resource')
        );

        $client->getConfig()->setApiKey('Authorization', $token);
        return $client;
    }

    /**
     * @return string
     */
    private function getFraudAdvicePayload(): string
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
     * @test
     * @return void
     * @throws Exception
     * @group common-fraud-service-integration
     */
    public function it_should_return_authenticate_3ds_when_forcing_3ds_soft_block_by_email_and_site_id(): void
    {
        if (!config('app.feature.common_fraud_enable_for.process.new_credit_card')) {
            $this->markTestSkipped('Common services fraud not enabled.');
            return;
        }

        $response = $this->rocketgateInitWithMenSiteForcingDeclineByAmount();
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
         * Attempt 1 - with force3ds email
         */
        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        // If it is falling, check if this email is proper configured in fraud recommendation panel
        if (!$this->emailConfiguredInFraudRecommendationShouldReturnForce3ds($this->force3DsEmail)) {
            self::fail(
                'Static email not configure on fraud recommendation panel. Use a new one or contact the maintainer.'
            );
        }
        $processPurchasePayload['member']['email'] = $this->force3DsEmail;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($params['header'])
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $result = json_decode($this->response->getContent(), true);

        $this->assertEquals('authenticate3D', $result["nextAction"]['type']);
        $this->assertArrayHasKey('authenticateUrl', $result["nextAction"]['threeD']);
    }

    /**
     * @return ProcessPurchaseWithFraudRulesTest
     * @throws Exception
     */
    private function rocketgateInitWithMenSiteForcingDeclineByAmount()
    {
        $payload = $this->initPurchasePayload(
            ProcessPurchaseBase::TESTING_SITE,
            CurrencyCode::JPY
        );

        $headers = $this->initPurchaseHeaders();

        $payload['amount']                             = 0.01;
        $payload['rebillAmount']                       = 0.01;
        $payload['tax']['initialAmount']['afterTaxes'] = 0.01;
        $payload['tax']['rebillAmount']['afterTaxes']  = 0.01;

        $headers['X-Force-Cascade'] = 'test-rocketgate';

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );
    }
}
