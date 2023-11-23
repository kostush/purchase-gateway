<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseDeclinedTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_for_declined_purchase_should_contain_x_auth_token(): string
    {
        //In order to get declined purchase we put amount 0.01
        //$this->initPurchasePayload()['amount'] = 0.01;

        $response = $this->initPurchaseProcessWithOneCrossSaleWithoutTax(true);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_for_declined_purchase_should_contain_x_auth_token
     *
     * @param string $token token.
     *
     * @return array
     */
    public function process_purchase_declined_should_return_success(string $token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->payloadToReceiveDeclinedResponse(),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return [
            'payload'   => json_decode($this->response->getContent(), true),
            'authToken' => $token
        ];
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_success_key(array $response): void
    {
        $this->assertArrayHasKey('success', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_not_contain_transactionId_key(array $response): void
    {
        $this->assertArrayNotHasKey('transactionId', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_sessionId_key(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_billerName_key(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_success_with_false_value(array $response): void
    {
        $this->assertFalse($response['payload']['success']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_purchaseId_key(array $response): void
    {
        $this->assertArrayHasKey('purchaseId', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_memberId_key(array $response): void
    {
        $this->assertArrayHasKey('memberId', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_declined_response_should_contain_digest_key(array $response): void
    {
        $this->assertArrayHasKey('digest', $response['payload']);
    }

    /**
     * @test
     * @depends process_purchase_declined_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_second_submit_attempt_should_be_successful(array $response): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($response['authToken'])
        );

        $response->assertResponseStatus(Response::HTTP_OK);
        $response = json_decode($this->response->getContent(), true);

        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_for_declined_transaction_should_contain_x_auth_token(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_for_declined_transaction_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_declined_transaction_should_return_success($token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_complete_successfully_a_declined_transaction_using_rocketgate()
    {
        //force a failed transaction with rocketgate
        $response = $this->initDeclinedPurchaseProcessWithOneCrossSale(true);
        $response->seeHeader('X-Auth-Token');

        //first attempt
        $purchaseDeclined = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $purchaseDeclined->assertResponseStatus(Response::HTTP_OK);

        return json_decode($purchaseDeclined->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_complete_successfully_a_declined_transaction_using_rocketgate
     *
     * @param array $purchaseResponse
     *
     * @return array
     */
    public function it_should_have_errorClassification_in_the_response(array $purchaseResponse): array
    {
        $this->assertArrayHasKey('errorClassification', $purchaseResponse);

        return $purchaseResponse['errorClassification'];
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     *
     * @param array $errorClassification
     */
    public function error_classification_should_contain_groupDecline(array $errorClassification): void
    {
        $this->assertArrayHasKey('groupDecline', $errorClassification);
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     *
     * @param array $errorClassification
     */
    public function error_classification_should_contain_errorType(array $errorClassification): void
    {
        $this->assertArrayHasKey('errorType', $errorClassification);
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     *
     * @param array $errorClassification
     */
    public function error_classification_should_contain_groupMessage(array $errorClassification): void
    {
        $this->assertArrayHasKey('groupMessage', $errorClassification);
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     *
     * @param array $errorClassification Error classification.
     *
     * @return void
     */
    public function error_classification_should_contain_recommendedAction(array $errorClassification): void
    {
        $this->assertArrayHasKey('recommendedAction', $errorClassification);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_complete_successfully_a_declined_transaction_using_netbilling(): void
    {
        //force a failed transaction with netbilling
        $response = $this->initDeclinedPurchaseProcessWithOneCrossSale(false, true);
        $response->seeHeader('X-Auth-Token');

        $purchaseDeclined = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $purchaseDeclined->assertResponseStatus(Response::HTTP_OK);

        $responsePurchase = json_decode($purchaseDeclined->response->getContent(), true);
        $this->assertArrayHasKey('errorClassification', $responsePurchase);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_complete_successfully_a_declined_NSF_transaction()
    {
        // payload
        $payload                                       = $this->initPurchasePayload();
        $payload['currency']                           = "USD";
        $payload['amount']                             = 0.02;
        $payload['rebillAmount']                       = 0.02;
        $payload['tax']['initialAmount']['afterTaxes'] = 0.02;
        $payload['tax']['rebillAmount']['afterTaxes']  = 0.02;

        // headers
        $headers                    = $this->initPurchaseHeaders();
        $headers['X-Force-Cascade'] = 'test-rocketgate';

        $responseInit = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );

        $responseInit->seeHeader('X-Auth-Token');

        $purchaseDeclined = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $purchaseDeclined->assertResponseStatus(Response::HTTP_OK);

        return json_decode($purchaseDeclined->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_complete_successfully_a_declined_NSF_transaction
     *
     * @param array $response Purchase Response.
     *
     * @return void
     */
    public function it_should_contain_response_for_NSF_transaction(array $response): void
    {
        $this->assertArrayHasKey('isNsf', $response, self::FLAKY_TEST);
        $this->assertTrue($response['isNsf']);
        $this->assertFalse($response['success']);
        $this->assertEquals(FinishProcess::TYPE, $response['nextAction']['type']);
    }

    /**
     * @return array
     */
    private function payloadToReceiveDeclinedResponse(): array
    {
        return [
            'siteId'  => ProcessPurchaseBase::TESTING_SITE,
            'member'  => [
                'email'       => $this->faker->email,
                'username'    => $this->faker->userName,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'ccNumber'            => self::INVALID_CC_NUMBER,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }
}