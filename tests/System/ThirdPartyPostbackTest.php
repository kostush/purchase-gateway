<?php

namespace Tests\System;

use Illuminate\Http\Response;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DatabasePurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class ThirdPartyPostbackTest extends ProcessPurchaseBase
{
    /**
     * @var SodiumCryptService
     */
    private $cryptService;

    /**
     * @var JsonWebTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var DatabasePurchaseProcessHandler
     */
    private $purchaseProcessRepository;

    /**
     * @var string
     */
    private $baseUri = '/api/v1/purchase/thirdParty/postback/';

    /**
     * @var string
     */
    private $redirectBaseUri = '/api/v1/purchase/thirdParty/redirect/';

    /**
     * @var string
     */
    private $returnBaseUri = '/api/v1/purchase/thirdParty/return/';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->cryptService   = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );
        $this->tokenGenerator = new JsonWebTokenGenerator();

        $this->purchaseProcessRepository = new DatabasePurchaseProcessHandler(
            new PurchaseProcessRepository(app()->make('em'))
        );
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_success_response_when_receiving_a_valid_postback(): array
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

        $this->get($this->redirectBaseUri . $successJwt);

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $url = $this->baseUri . $initResponse['sessionId'];

        $response = $this->json(
            'POST',
            $url,
            [
                'payload' => $this->postbackPayloadForEpoch(
                    $initResponse['sessionId'],
                    (string) $purchaseProcess->retrieveMainPurchaseItem()
                        ->transactionCollection()
                        ->first()
                        ->transactionId()
                ),
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($response->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_response_when_receiving_a_valid_postback
     * @param array $response Response after postback
     * @return void
     */
    public function it_should_return_session_id_when_receiving_a_valid_postback(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends it_should_return_success_response_when_receiving_a_valid_postback
     * @param array $response Response after postback
     * @return void
     */
    public function it_should_return_result_when_receiving_a_valid_postback(array $response): void
    {
        $this->assertArrayHasKey('result', $response);
    }

    /**
     * @test
     * @depends it_should_return_success_response_when_receiving_a_valid_postback
     * @param array $response Response after postback
     * @return void
     */
    public function it_should_return_success_result_when_receiving_a_valid_postback(array $response): void
    {
        $this->assertSame('success', $response['result']);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_success_response_when_receiving_a_valid_rebill_postback(): array
    {
        $this->markTestSkipped('Skipped as qysso functionality is not working properly at the moment');

        // init

        $initPayload = $this->initPurchasePayload();

        $initPayload['paymentType']   = 'banktransfer';
        $initPayload['paymentMethod'] = 'zelle';
        unset($initPayload['crossSellOptions']);
        unset($initPayload['tax']);

        $initResponse = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPayload,
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-qysso'])
        );
        $initResponse->seeHeader('X-Auth-Token');

        $token = (string) $this->response->headers->get('X-Auth-Token');

        $initResponse = json_decode($initResponse->response->getContent(), true);

        // process

        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPayload['payment'] = [
            'method' => 'zelle'
        ];

        $processResponse = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders($token)
        );

        $processResponse = json_decode($processResponse->response->getContent(), true);

        // redirect

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        $url = $this->baseUri . $initResponse['sessionId'];

        // return

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams = $this->postbackPayloadForQysso($transactionId);

        $returnResponse = $this->get($this->returnBaseUri . $successJwt . '?' . http_build_query($queryParams));

        $returnResponse = json_decode($returnResponse->response->getContent(), true);

        // rebill postback

        $url = $this->baseUri . $initResponse['sessionId'];

        $rebillPostbackResponse = $this->json(
            'POST',
            $url,
            [
                'payload' => $this->postbackPayloadForQysso(
                    (string) $purchaseProcess->retrieveMainPurchaseItem()
                        ->transactionCollection()
                        ->first()
                        ->transactionId()
                ),
                'type' => ThirdPartyPostbackCommandHandlerFactory::REBILL
            ]
        );

        $rebillPostbackResponse->assertResponseStatus(Response::HTTP_OK);

        return json_decode($rebillPostbackResponse->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_response_when_receiving_a_valid_rebill_postback
     * @param array $response Response after postback
     * @return void
     */
    public function it_should_return_session_id_when_receiving_a_valid_rebill_postback(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends it_should_return_success_response_when_receiving_a_valid_rebill_postback
     * @param array $response Response after postback
     * @return void
     */
    public function it_should_return_result_when_receiving_a_valid_rebill_postback(array $response): void
    {
        $this->assertArrayHasKey('result', $response);
    }

    /**
     * @test
     * @depends it_should_return_success_response_when_receiving_a_valid_rebill_postback
     * @param array $response Response after postback
     * @return void
     */
    public function it_should_return_success_result_when_receiving_a_valid_rebill_postback(array $response): void
    {
        $this->assertSame('success', $response['result']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_fail_result_when_receiving_an_invalid_postback(): void
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

        $this->get($this->redirectBaseUri . $successJwt);

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $url = $this->baseUri . $initResponse['sessionId'];

        $incorrectPayload = $this->postbackPayloadForEpoch(
            $initResponse['sessionId'],
            (string) $purchaseProcess->retrieveMainPurchaseItem()
                ->transactionCollection()
                ->first()
                ->transactionId()
        );

        $incorrectPayload['epoch_digest'] = 'wrong-digest';

        $response = $this->json(
            'POST',
            $url,
            [
                'payload' => $incorrectPayload,
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );

        $result = json_decode($response->response->getContent(), true);

        $this->assertSame('fail', $result['result']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_not_found_when_session_id_does_not_exist(): void
    {
        $url = $this->baseUri . $this->faker->uuid;

        $response = $this->json(
            'POST',
            $url,
            [
                'payload' => ['ngTransactionId' => $this->faker->uuid],
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );

        $responseCode = $response->response->status();

        $this->assertSame(Response::HTTP_NOT_FOUND, $responseCode);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_bad_request_when_receiving_a_invalid_session_id(): void
    {
        $url = $this->baseUri . 'invalid-session-id';

        $response = $this->json(
            'POST',
            $url,
            [
                'payload' => ['ngTransactionId' => $this->faker->uuid],
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );

        $responseCode = $response->response->status();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $responseCode);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_bad_request_when_receiving_postback_with_an_empty_body(): void
    {
        $url = $this->baseUri . $this->faker->uuid;

        $response = $this->json('POST', $url, [
            'payload' => [],
            'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE]
        );

        $responseCode = $response->response->status();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $responseCode);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_404_when_transaction_not_found(): void
    {
        // init
        $initPayload = $this->initPurchasePayload();

        $initPayload['paymentType']   = 'banktransfer';
        $initPayload['paymentMethod'] = 'zelle';
        unset($initPayload['crossSellOptions']);
        unset($initPayload['tax']);

        $initResponse = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPayload,
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-qysso'])
        );
        $initResponse->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');
        $initResponse = json_decode($initResponse->response->getContent(), true);

        // process
        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPayload['payment'] = [
            'method' => 'zelle'
        ];

        $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders($token)
        );

        // postback
        $payload = [
            "type"    => "charge",
            "payload" => [
                "reply_code"      => "008",
                "reply_desc"      => "Transaction declined",
                "trans_id"        => $this->faker->randomNumber('4'),
                "trans_date"      => $this->faker->dateTime(),
                "trans_amount"    => "9.99",
                "trans_currency"  => "1",
                "trans_order"     => $this->faker->uuid,
                "merchant_id"     => $this->faker->randomNumber('8'),
                "client_fullname" => $this->faker->name,
                "client_phone"    => $this->faker->phoneNumber,
                "client_email"    => $this->faker->email,
                "payment_details" => "DirectDebit",
                "signature"       => "SomeRandomKey"
            ],
        ];

        $url = $this->baseUri . $initResponse['sessionId'];

        $response = $this->json('POST', $url, [
                'payload' => $payload,
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE]
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->response->status());
    }

    /**
     * @test
     * @throws \ProBillerNG\Crypt\UnableToEncryptException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException
     */
    public function it_should_return_422_when_transaction_service_returns_400_for_decode_transaction_already_processed_failure(): void
    {
        $this->markTestSkipped('To run manually: first comment out "if ($transaction->transactionInformation()->status() === Transaction::STATUS_PENDING" in ThirdPartyPostbackCommandHandler::execute ');
        // init
        $initPayload = $this->initPurchasePayload();

        $initPayload['paymentType']   = 'banktransfer';
        $initPayload['paymentMethod'] = 'zelle';
        $initPayload['currency']      = 'USD';
        unset($initPayload['crossSellOptions']);
        unset($initPayload['tax']);

        $initResponse = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPayload,
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-qysso'])
        );
        $initResponse->seeHeader('X-Auth-Token');

        $token = (string) $this->response->headers->get('X-Auth-Token');

        $initResponse = json_decode($initResponse->response->getContent(), true);

        // process
        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPayload['payment'] = [
            'method' => 'zelle'
        ];
        $processResponse = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders($token)
        );

        // redirect
        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        // postback
        $url             = $this->baseUri . $initResponse['sessionId'];
        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);
        $transactionId   = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $postbackResponse = $this->json(
            'POST',
            $url,
            [
                'payload' => $this->postbackPayloadForQysso($transactionId, 'SEK83K2Z2D'),
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );

        $postbackResponse = $this->json(
            'POST',
            $url,
            [
                'payload' => $this->postbackPayloadForQysso($transactionId, 'SEK83K2Z2D'),
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );
        $postbackResponse->assertResponseStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_424_when_transaction_service_returns_400_for_decode_signature_failure(): void
    {
        $this->markTestSkipped('Skipped as qysso functionality is not working properly at the moment');

        // init
        $initPayload = $this->initPurchasePayload();

        $initPayload['paymentType']   = 'banktransfer';
        $initPayload['paymentMethod'] = 'zelle';
        $initPayload['currency']      = 'USD';
        unset($initPayload['crossSellOptions']);
        unset($initPayload['tax']);

        $initResponse = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPayload,
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-qysso'])
        );
        $initResponse->seeHeader('X-Auth-Token');

        $token = (string) $this->response->headers->get('X-Auth-Token');

        $initResponse = json_decode($initResponse->response->getContent(), true);

        // process
        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPayload['payment'] = [
            'method' => 'zelle'
        ];
        $processResponse = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders($token)
        );

        // redirect
        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        // postback
        $url             = $this->baseUri . $initResponse['sessionId'];
        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);
        $transactionId   = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $postbackResponse = $this->json(
            'POST',
            $url,
            [
                'payload' => $this->postbackPayloadForQysso($transactionId),
                'type' => ThirdPartyPostbackCommandHandlerFactory::CHARGE
            ]
        );

        $postbackResponse->assertResponseStatus(Response::HTTP_FAILED_DEPENDENCY);
    }
}
