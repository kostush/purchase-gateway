<?php

namespace Tests\System\ThirdParty;

use DOMDocument;
use Exception;
use Illuminate\Http\Response;
use ProBillerNG\Crypt\Sodium\InvalidPrivateKeySizeException;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\Crypt\UnableToEncryptException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DatabasePurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class ThirdPartyReturnTest extends ProcessPurchaseBase
{
    /**
     * @var string
     */
    private $baseUri = '/api/v1/purchase/thirdParty/return/';

    /**
     * @var string
     */
    private $redirectBaseUri = '/api/v1/purchase/thirdParty/redirect/';

    /**
     * @var DatabasePurchaseProcessHandler
     */
    private $purchaseProcessRepository;

    /**
     * @var SodiumCryptService
     */
    private $cryptService;

    /**
     * @var JsonWebTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @throws InvalidPrivateKeySizeException
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->cryptService = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $this->tokenGenerator            = new JsonWebTokenGenerator();
        $this->purchaseProcessRepository = new DatabasePurchaseProcessHandler(
            new PurchaseProcessRepository(app()->make('em'))
        );
    }

    /**
     * @test
     * @return string
     * @throws InitPurchaseInfoNotFoundException
     * @throws UnableToEncryptException
     */
    public function it_should_return_200_status_when_receiving_a_valid_request(): string
    {
        $this->markTestSkipped('We no longer support Epoch biller.');

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload(),
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-epoch'])
        );
        $initPurchase->seeHeader('X-Auth-Token');
        $initResponse = json_decode($initPurchase->response->getContent(), true);

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams = $this->postbackPayloadForEpoch($initResponse['sessionId'], $transactionId);

        $response = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_return_a_html_response(string $response): void
    {
        $this->assertTrue($this->isHTML($response));
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_success(string $response): void
    {
        $this->assertNotEmpty($this->getHtmlData($response)['success']);
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_true(string $response): void
    {
        $json        = $this->getHtmlData($response)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertTrue($jsonDecoded->success);
    }

    /**
     * @test
     * @return string
     * @throws UnableToEncryptException
     * @throws InitPurchaseInfoNotFoundException
     */
    public function it_should_return_200_status_with_success_false_if_invalid_request_is_received(): string
    {
        $this->markTestSkipped('We no longer support Epoch biller.');
        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload(),
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-epoch'])
        );
        $initPurchase->seeHeader('X-Auth-Token');
        $initResponse = json_decode($initPurchase->response->getContent(), true);

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams = $this->postbackPayloadForEpoch($initResponse['sessionId'], $transactionId);

        $queryParams['epoch_digest'] = 'wrong-digest';

        $response = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_200_status_with_success_false_if_invalid_request_is_received
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_false(string $response): void
    {
        $json        = $this->getHtmlData($response)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertFalse($jsonDecoded->success);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_404_not_found_status_code_when_receiving_a_random_transaction_id(): void
    {
        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload(),
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-epoch'])
        );
        $initPurchase->seeHeader('X-Auth-Token');
        $initResponse = json_decode($initPurchase->response->getContent(), true);

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        $queryParams = [
            'email'            => $this->faker->email,
            'name'             => 'John Snow',
            'postalcode'       => $this->faker->postcode,
            'zip'              => '111111',
            'prepaid'          => 'N',
            'country'          => $this->faker->country,
            'ipaddress'        => '11.111.11.111',
            'submit_count'     => 1,
            'trans_amount'     => 14.95,
            'trans_amount_usd' => 15.89,
            'trans_currency'   => 'EUR',
            'transaction_id'   => '122340927',
            'amount'           => 15.89,
            'currency'         => 'EUR',
            'localamount'      => 14.95,
            'payment_type'     => 'cc',
            'payment_subtype'  => 'VS',
            'last4'            => '9165',
            'order_id'         => '2342254804',
            'member_id'        => '2342254804',
            'pi_code'          => 'InvoiceProduct76897',
            'session_id'       => '4bc28371-1839-44fe-85bb-cd4e6af0a726',
            'ngSessionId'      => $initResponse['sessionId'],
            'ngTransactionId'  => $this->faker->uuid,
            'ans'              => 'Y744660UU%20%7C2342554806',
            'epoch_digest'     => '8ae7d3e38a14cb34b9039b6c2665d329',
            'event_datetime'   => '2020-04-29T08%3A39%3A43.179Z',
        ];

        $response = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @return void
     * @throws UnableToEncryptException
     */
    public function it_should_return_not_found_when_session_id_not_found(): void
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($this->faker->uuid)
            ]
        );

        $this->get($this->redirectBaseUri . $jwt);

        $queryParams = [];

        $response = $this->get($this->baseUri . $jwt . '?' . http_build_query($queryParams));

        $response->assertResponseStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     * @return void
     * @throws UnableToEncryptException
     */
    public function it_should_return_bad_request_400_when_session_is_invalid(): void
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt('randomStuff')
            ]
        );

        $this->get($this->redirectBaseUri . $jwt);

        $queryParams = [];

        $response = $this->get($this->baseUri . $jwt . '?' . http_build_query($queryParams));

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->assertTrue(
            mb_stripos($response->response->getContent(), 'Invalid session id!') !== false
        );
    }

    /**
     * @test
     * @return void
     * @throws UnableToEncryptException
     * @throws InitPurchaseInfoNotFoundException
     */
    public function it_should_return_400_status_when_session_already_processed(): void
    {
        $this->markTestSkipped('We no longer support Epoch biller.');
        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload(),
            array_merge($this->initPurchaseHeaders(), ['X-Force-Cascade' => 'test-epoch'])
        );
        $initPurchase->seeHeader('X-Auth-Token');
        $initResponse = json_decode($initPurchase->response->getContent(), true);

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()
            ->transactionCollection()
            ->first()
            ->transactionId();

        $queryParams = [
            'email'            => $this->faker->email,
            'name'             => 'John Snow',
            'postalcode'       => $this->faker->postcode,
            'zip'              => '111111',
            'prepaid'          => 'N',
            'country'          => $this->faker->country,
            'ipaddress'        => '11.111.11.111',
            'submit_count'     => 1,
            'trans_amount'     => 14.95,
            'trans_amount_usd' => 15.89,
            'trans_currency'   => 'EUR',
            'transaction_id'   => '122340927',
            'amount'           => 15.89,
            'currency'         => 'EUR',
            'localamount'      => 14.95,
            'payment_type'     => 'cc',
            'payment_subtype'  => 'VS',
            'last4'            => '9165',
            'order_id'         => '2342254804',
            'member_id'        => '2342254804',
            'pi_code'          => 'InvoiceProduct76897',
            'session_id'       => '4bc28371-1839-44fe-85bb-cd4e6af0a726',
            'ngSessionId'      => $initResponse['sessionId'],
            'ngTransactionId'  => $transactionId,
            'ans'              => 'Y744660UU%20%7C2342554806',
            'epoch_digest'     => '8ae7d3e38a14cb34b9039b6c2665d329',
            'event_datetime'   => '2020-04-29T08%3A39%3A43.179Z',
        ];

        $firstCallResponse  = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));
        $secondCallResponse = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $secondCallResponse->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @return string
     * @throws InitPurchaseInfoNotFoundException
     * @throws UnableToEncryptException
     */
    public function it_should_return_200_status_when_receiving_a_valid_request_from_qysso(): string
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

        $this->json(
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

        // return

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams = $this->postbackPayloadForQysso($transactionId);

        $returnResponse = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $returnResponse->assertResponseStatus(Response::HTTP_OK);

        return $returnResponse->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request_from_qysso
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_return_a_html_response_with_qysso(string $response): void
    {
        $this->assertTrue($this->isHTML($response));
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_success_with_qysso(string $response): void
    {
        $this->assertNotEmpty($this->getHtmlData($response)['success']);
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_true_qysso(string $response): void
    {
        $json        = $this->getHtmlData($response)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertTrue($jsonDecoded->success);
    }

    /**
     * @test
     * @return void
     * @throws InitPurchaseInfoNotFoundException
     * @throws UnableToEncryptException
     */
    public function it_should_return_404_not_found_status_code_when_receiving_unexisting_transaction_id_from_qysso(): void
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

        // redirect

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        // return

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams          = $this->postbackPayloadForQysso($transactionId);
        $queryParams['Order'] = $this->faker->uuid;

        $returnResponse = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $returnResponse->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @return string
     * @throws InitPurchaseInfoNotFoundException
     * @throws UnableToEncryptException
     */
    public function it_should_return_200_status_when_receiving_a_valid_rebill_request_from_qysso(): string
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

        // redirect

        $successJwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt($initResponse['sessionId'])
            ]
        );

        $this->get($this->redirectBaseUri . $successJwt);

        // return

        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);

        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams = $this->postbackPayloadForQysso($transactionId);

        $returnResponse = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        // rebill return

        $queryParams['recur_seriesID']  = '555';
        $queryParams['recur_chargeNum'] = '2';

        $rebillReturnResponse = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $rebillReturnResponse->assertResponseStatus(Response::HTTP_OK);

        return $rebillReturnResponse->response->getContent();
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_rebill_request_from_qysso
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_return_a_html_response_after_rebill_return_with_qysso(string $response): void
    {
        $this->assertTrue($this->isHTML($response));
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_rebill_request_from_qysso
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_return_an_input_with_attribute_name_success_after_rebill_return_with_qysso(string $response): void
    {
        $this->assertNotEmpty($this->getHtmlData($response)['success']);
    }

    /**
     * @test
     * @depends it_should_return_200_status_when_receiving_a_valid_request
     * @param string $response Complete Response
     * @return void
     */
    public function it_should_contain_a_json_with_success_true_after_rebill_return_with_qysso(string $response): void
    {
        $json        = $this->getHtmlData($response)['success'];
        $jsonDecoded = json_decode($json);
        $this->assertTrue($jsonDecoded->success);
    }

    /**
     * @param string $response Response
     * @return bool
     */
    private function isHTML(string $response): bool
    {
        return $response !== strip_tags($response);
    }

    /**
     * @param string $response Auth Response
     * @return array
     */
    private function getHtmlData(string $response): array
    {
        $data['uri']     = '';
        $data['success'] = '';
        $data['error']   = '';

        $doc = new DOMDocument();
        $doc->loadHTML($response);

        $inputs = $doc->getElementsByTagName('input');

        foreach ($inputs as $input) {
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
     * @test
     * @return void
     * @throws InitPurchaseInfoNotFoundException
     * @throws UnableToEncryptException
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

        $this->json(
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

        // return
        $purchaseProcess = $this->purchaseProcessRepository->load($initResponse['sessionId']);
        $transactionId = (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId();

        $queryParams          = $this->postbackPayloadForQysso($transactionId);

        $returnResponse = $this->get($this->baseUri . $successJwt . '?' . http_build_query($queryParams));

        $returnResponse->assertResponseStatus(Response::HTTP_FAILED_DEPENDENCY);
    }
}
