<?php

declare(strict_types=1);

namespace System;

use Exception;
use Illuminate\Http\Response;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\Crypt\UnableToEncryptException;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SessionWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use Tests\System\InitPurchase\FraudCheckDependingOnSiteConfigurationTest;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * Class AuthenticateThreeDTest
 * @package System
 * @group   common-fraud-service-integration
 */
class AuthenticateThreeDTest extends ProcessPurchaseBase
{
    /**
     * @var SessionWebToken
     */
    private $tokenAuthService;

    /**
     * @var SodiumCryptService
     */
    private $cryptService;

    /**
     * @var JsonWebTokenGenerator
     */
    private $tokenGenerator;
    /**
     * @var string
     */
    private $baseUri = '/api/v1/purchase/threed/authenticate/';

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->tokenGenerator   = new JsonWebTokenGenerator();
        $this->tokenAuthService = new SessionWebToken(new JsonWebTokenGenerator());
        $this->cryptService     = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_success_200_when_session_found_and_valid(): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
            return;
        }

        $payload = $this->initPurchasePayload(
            ProcessPurchaseBase::TESTING_SITE,
            CurrencyCode::JPY
        );

        $payload['clientIp']          = FraudCheckDependingOnSiteConfigurationTest::$forceThreeDSIp;
        $payload['clientCountryCode'] = 'RO';
        $payload['redirectUrl']       = $this->faker->url;

        $initHeaders = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        unset($payload['crossSellOptions']);

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPayload['payment']['ccNumber'] = FraudCheckDependingOnSiteConfigurationTest::getTriggerThreeDSCardNo();

        // second attempt
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $processResponse = json_decode($response->response->getContent(), true);

        if (empty($processResponse['nextAction']['threeD']['authenticateUrl'])) {
            $this->fail(self::THREE_D_FLOW_NOT_TRIGGERED);
        }

        $response = $this->get($processResponse['nextAction']['threeD']['authenticateUrl']);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'name="toBank"') !== false
        );
    }

    /**
     * @test
     * @return void
     * @throws UnableToEncryptException
     */
    public function it_should_return_not_found_404_when_session_not_found(): void
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($this->faker->uuid)
            ]
        );

        $response = $this->get($this->baseUri . $jwt);

        $response->assertResponseStatus(Response::HTTP_NOT_FOUND);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'The session does not exist') !== false
        );
    }

    /**
     * @test
     * @return void
     * @throws UnableToEncryptException
     */
    public function it_should_return_bad_request_400_when_invalid_session_provided(): void
    {
        $successJwt = 'invalid session';

        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($successJwt)
            ]
        );

        $response = $this->get($this->baseUri . $jwt);

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'Invalid session id') !== false
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_bad_request_400_when_purchase_was_already_processed(): void
    {
        $initPurchase = $this->initPurchaseProcessWithOneCrossSale(true);
        $initResponse = json_decode($initPurchase->response->getContent(), true);

        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $response = $this->get($this->baseUri . $jwt);

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'was already processed') !== false
        );
    }
}
