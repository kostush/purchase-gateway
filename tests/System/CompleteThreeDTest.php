<?php

declare(strict_types=1);

namespace System;

use DOMDocument;
use Exception;
use Illuminate\Http\Response;
use ProBillerNG\Crypt\Sodium\InvalidPrivateKeySizeException;
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
 * Class CompleteThreeDTest
 * @package System
 * @group   common-fraud-service-integration
 */
class CompleteThreeDTest extends ProcessPurchaseBase
{
    /** @var string */
    private $baseUri = '/api/v1/purchase/threed/complete/';

    /** @var array */
    private $PaRes = ['PaRes' => 'SimulatedPARES10001000E00B000'];

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
     * @var bool
     */
    private $simplifiedCompleteFeatureFlag;

    /**
     * @return void
     * @throws InvalidPrivateKeySizeException
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
     * @return string
     * @throws Exception
     */
    public function it_should_return_200_status(): string
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
        }

        // JPY triggers using a merchant id which supports 3DS1
        $payload = $this->initPurchasePayload(
            ProcessPurchaseBase::TESTING_SITE,
            CurrencyCode::JPY
        );

        $payload['clientIp']          = FraudCheckDependingOnSiteConfigurationTest::$forceThreeDSIp;
        $payload['clientCountryCode'] = 'RO';

        $initHeaders = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload                        = $this->processPurchasePayloadWithOneSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = FraudCheckDependingOnSiteConfigurationTest::getTriggerThreeDSCardNo();

        $processCall = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($processCall->response->getContent(), true);

        if (empty($processResponse['nextAction']['threeD']['authenticateUrl'])) {
            $this->fail(self::THREE_D_FLOW_NOT_TRIGGERED);
        }

        $authCall = $this->get($processResponse['nextAction']['threeD']['authenticateUrl']);

        $completeData = $this->getHtmlData($authCall->response->getContent());

        $completeCall = $this->json(
            'POST',
            $completeData['completeUri'],
            [
                'PaRes' => $completeData['PaRes']
            ],
            []
        );

        $completeCall->assertResponseStatus(Response::HTTP_OK);

        return $completeCall->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_200_status
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_return_a_html_response(string $completeResponse): void
    {
        $this->assertTrue($this->isHTML($completeResponse));
    }

    /**
     * @test
     * @depends it_should_return_200_status
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_success(string $completeResponse): void
    {
        $this->assertNotEmpty($this->getHtmlData($completeResponse)['success']);
    }

    /**
     * @test
     * @depends it_should_return_200_status
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_true(string $completeResponse): void
    {
        $json        = $this->getHtmlData($completeResponse)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertTrue($jsonDecoded->success);
    }

    /**
     * @test
     * @depends it_should_return_200_status
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_crossSells_not_empty(string $completeResponse): void
    {
        $json        = $this->getHtmlData($completeResponse)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertNotEmpty($jsonDecoded->crossSells);
    }

    /**
     * @test
     * @return string
     * @throws Exception
     */
    public function it_should_return_success_200_when_invalid_pares_provided(): string
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
        }

        // JPY triggers using a merchant id which supports 3DS1
        $payload = $this->initPurchasePayload(
            ProcessPurchaseBase::TESTING_SITE,
            CurrencyCode::JPY
        );

        $payload['clientIp']          = FraudCheckDependingOnSiteConfigurationTest::$forceThreeDSIp;
        $payload['clientCountryCode'] = 'RO';

        $initHeaders = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload                        = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = FraudCheckDependingOnSiteConfigurationTest::getTriggerThreeDSCardNo();

        $processCall = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($processCall->response->getContent(), true);

        if (empty($processResponse['nextAction']['threeD']['authenticateUrl'])) {
            $this->fail(self::THREE_D_FLOW_NOT_TRIGGERED);
        }

        $authCall = $this->get($processResponse['nextAction']['threeD']['authenticateUrl']);

        $completeData = $this->getHtmlData($authCall->response->getContent());

        $completeCall = $this->json(
            'POST',
            $completeData['completeUri'],
            $this->PaRes,
            []
        );

        $completeCall->assertResponseStatus(Response::HTTP_OK);

        return $completeCall->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_success_200_when_invalid_pares_provided
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_success_when_purchase_failed(
        string $completeResponse
    ): void {
        $this->assertNotEmpty($this->getHtmlData($completeResponse)['success']);
    }

    /**
     * @test
     * @depends it_should_return_success_200_when_invalid_pares_provided
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_false(string $completeResponse): void
    {
        $json        = $this->getHtmlData($completeResponse)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertFalse($jsonDecoded->success);
    }

    /**
     * @test
     * @return string
     * @throws Exception
     */
    public function it_should_return_success_200_when_invalid_md_provided(): string
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
        }

        // JPY triggers using a merchant id which supports 3DS1
        $payload = $this->initPurchasePayload(
            ProcessPurchaseBase::TESTING_SITE,
            CurrencyCode::JPY
        );

        $payload['clientIp']          = FraudCheckDependingOnSiteConfigurationTest::$forceThreeDSIp;
        $payload['clientCountryCode'] = 'RO';

        $initHeaders = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload                        = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = FraudCheckDependingOnSiteConfigurationTest::getTriggerThreeDSCardNo();

        $processCall = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($processCall->response->getContent(), true);

        if (empty($processResponse['nextAction']['threeD']['authenticateUrl'])) {
            $this->fail(self::THREE_D_FLOW_NOT_TRIGGERED);
        }

        $authCall = $this->get($processResponse['nextAction']['threeD']['authenticateUrl']);

        $completeData = $this->getHtmlData($authCall->response->getContent());

        $completeCall = $this->json(
            'POST',
            $completeData['completeUri'],
            ['MD' => true],
            []
        );

        $completeCall->assertResponseStatus(Response::HTTP_OK);

        return $completeCall->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_success_200_when_invalid_md_provided
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_success_when_purchase_failed_with_invalid_md(
        string $completeResponse
    ): void {
        $this->assertNotEmpty($this->getHtmlData($completeResponse)['success']);
    }

    /**
     * @test
     * @depends it_should_return_success_200_when_invalid_md_provided
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_false_when_purchase_failed_with_invalid_md(
        string $completeResponse
    ): void {
        $json        = $this->getHtmlData($completeResponse)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertFalse($jsonDecoded->success);
    }

    /**
     * @test
     * @return void
     * @throws UnableToEncryptException
     */
    public function it_should_return_not_found_404_when_session_not_found()
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($this->faker->uuid)
            ]
        );

        $response = $this->json('POST', $this->baseUri . $jwt, $this->PaRes);

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
    public function it_should_return_bad_request_400_when_session_is_invalid()
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt("fake session id")
            ]
        );

        $response = $this->json('POST', $this->baseUri . $jwt, $this->PaRes);

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'Invalid session id') !== false
        );
    }

    /**
     * @test
     * @return string
     * @throws Exception
     */
    public function it_should_return_bad_request_400_when_purchase_was_already_processed(): string
    {
        $initPurchase = $this->initPurchaseProcessWithOneCrossSale();
        $initResponse = json_decode($initPurchase->response->getContent(), true);

        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $response = $this->json('POST', $this->baseUri . $jwt, $this->PaRes);

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'was already processed') !== false
        );

        return $response->response->getContent();
    }

    /**
     * @test
     * @return string
     * @throws Exception
     */
    public function it_should_return_bad_request_400_when_empty_or_no_pares_provided(): string
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
        }

        // JPY triggers using a merchant id which supports 3DS1
        $payload = $this->initPurchasePayload(
            ProcessPurchaseBase::TESTING_SITE,
            CurrencyCode::JPY
        );

        $payload['clientIp']          = FraudCheckDependingOnSiteConfigurationTest::$forceThreeDSIp;
        $payload['clientCountryCode'] = 'RO';

        $initHeaders = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload                        = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = FraudCheckDependingOnSiteConfigurationTest::getTriggerThreeDSCardNo();

        $processCall = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($processCall->response->getContent(), true);

        if (empty($processResponse['nextAction']['threeD']['authenticateUrl'])) {
            $this->fail(self::THREE_D_FLOW_NOT_TRIGGERED);
        }

        $authCall = $this->get($processResponse['nextAction']['threeD']['authenticateUrl']);

        $completeData = $this->getHtmlData($authCall->response->getContent());

        $completeCall = $this->json(
            'POST',
            $completeData['completeUri'],
            ['PaRes' => ''],
            []
        );

        $completeCall->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return $completeCall->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_bad_request_400_when_purchase_was_already_processed
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_error(string $completeResponse): void
    {
        $this->assertNotEmpty($this->getHtmlData($completeResponse)['error']);
    }

    /**
     * @test
     * @depends it_should_return_bad_request_400_when_purchase_was_already_processed
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_an_error_code(string $completeResponse): void
    {
        $json        = $this->getHtmlData($completeResponse)['error'];
        $jsonDecoded = json_decode($json);
        $this->assertNotEmpty($jsonDecoded->code);
    }

    /**
     * @test
     * @return string
     * @throws Exception
     */
    public function it_should_return_400_status_when_forcing_cascade_netbilling_and_forcing_threeDs()
    {
        $this->setUp();
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
        }

        // JPY triggers using a merchant id which supports 3DS1
        $payload                      = $this->initPurchasePayload(ProcessPurchaseBase::TESTING_SITE, CurrencyCode::JPY);
        $payload['clientIp']          = FraudCheckDependingOnSiteConfigurationTest::$forceThreeDSIp;
        $payload['clientCountryCode'] = 'RO';

        $initHeaders                    = $this->initPurchaseHeaders();
        $initHeaders['X-Force-Cascade'] = 'test-netbilling';

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $initPurchase->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param string $response Auth Response
     * @return array
     */
    private function getHtmlData(string $response): array
    {
        $data['PaRes']       = '';
        $data['completeUri'] = '';
        $data['success']     = '';
        $data['error']       = '';

        $doc = new DOMDocument();
        $doc->loadHTML($response);
        $inputs = $doc->getElementsByTagName('input');
        foreach ($inputs as $input) {
            if ($input->getAttribute('name') == 'PaReq') {
                $data['PaRes'] = str_replace(
                    'PAREQ',
                    'PARES',
                    $input->getAttribute('value')
                );
            }
            if ($input->getAttribute('name') == 'TermUrl') {
                $data['completeUri'] = $input->getAttribute('value');
            }
            if ($input->getAttribute('name') == 'success') {
                $data['success'] = $input->getAttribute('value');
            }
            if ($input->getAttribute('name') == 'error') {
                $data['error'] = $input->getAttribute('value');
            }
        }

        return $data;
    }

    /**
     * @param string $response Response
     * @return bool
     */
    private function isHTML(string $response): bool
    {
        return $response !== strip_tags($response);
    }
}
