<?php

namespace System;

use Illuminate\Http\Response;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SessionWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class ThirdPartyRedirectTest extends ProcessPurchaseBase
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
    private $baseUri = '/api/v1/purchase/thirdParty/redirect/';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->tokenAuthService = new SessionWebToken(new JsonWebTokenGenerator());
        $this->cryptService     = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );
        $this->tokenGenerator   = new JsonWebTokenGenerator();
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_success_200_for_redirect_to_third_party(): void
    {
        $this->markTestSkipped('We no longer support Epoch biller.');
        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload(),
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-epoch'])
        );
        $response->seeHeader('X-Auth-Token');
        $initResponse = json_decode($response->response->getContent(), true);

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $response = $this->get($this->baseUri . $successJwt);

        $response->assertResponseStatus(Response::HTTP_OK);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'Secure redirect to biller') !== false
        );
    }

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function it_should_return_success_200_for_redirect_to_third_party_for_qysso(): string
    {
        $this->markTestSkipped('Qysso currently not working properly.');

        $initPayload = $this->initPurchasePayload();

        $initPayload['paymentType']   = 'banktransfer';
        $initPayload['paymentMethod'] = 'zelle';
        $initPayload['currency']      = 'USD';

        $initResponse = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPayload,
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-qysso'])
        );
        $initResponse->seeHeader('X-Auth-Token');

        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPayload['payment'] = [
            'method' => 'zelle'
        ];

        $processResponse = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );
        $processResponse = json_decode($processResponse->response->getContent(), true);

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($processResponse['sessionId'])
            ]
        );

        $response = $this->get($this->baseUri . $successJwt);

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_success_200_for_redirect_to_third_party_for_qysso
     * @param string $response Response
     * @return void
     */
    public function it_should_redirect_to_biller_site(string $response): void
    {
        $this->markTestSkipped('Skipped as qysso functionality is not working properly at the moment');

        $this->assertTrue(
            mb_stripos($response, 'Secure redirect to biller') !== false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Crypt\UnableToEncryptException
     */
    public function it_should_return_not_found_404_for_redirect_to_third_party(): void
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
     * @throws \ProBillerNG\Crypt\UnableToEncryptException
     */
    public function it_should_return_not_found_400_for_redirect_to_third_party_when_session_invalid(): void
    {
        $successJwt = 'invalid session';

        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($successJwt)
            ]
        );

        $response = $this->get($this->baseUri . $jwt);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'Invalid session id') !== false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Crypt\UnableToEncryptException
     * @throws \Exception
     */
    public function it_should_return_not_found_400_for_redirect_to_third_party_when_session_was_already_processed(): void
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false, ProcessPurchaseBase::REALITY_KINGS_SITE_ID);
        $response->seeHeader('X-Auth-Token');
        $initResponse = json_decode($response->response->getContent(), true);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithOneSelectedCrossSale(ProcessPurchaseBase::REALITY_KINGS_SITE_ID),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

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
