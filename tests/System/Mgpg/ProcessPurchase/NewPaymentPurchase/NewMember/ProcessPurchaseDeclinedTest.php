<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

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
        // Force decline RG by passing 0,01
        $response = $this->initPurchaseProcessWithOneCrossSaleWithoutTax(true, [
            'amount' => 0.01,
        ]);

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
            $this->buildProcessPurchasePayload(),
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
    public function adapter_process_purchase_declined_response_should_not_contain_billerName_key(array $response): void
    {
        $this->assertArrayNotHasKey('billerName', $response['payload']);
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
     * @param array $purchaseResponse
     * @return array
     */
    public function it_should_have_errorClassification_in_the_response(array $purchaseResponse): array
    {
        $this->markTestSkipped("MGPG errorClassfification not supported yet.");
        $this->assertArrayHasKey('errorClassification', $purchaseResponse);
        return $purchaseResponse['errorClassification'];
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     * @param array $errorClassification
     */
    public function error_classification_should_contain_groupDecline(array $errorClassification): void
    {
        $this->markTestSkipped("MGPG errorClassfification not supported yet.");
        $this->assertArrayHasKey('groupDecline', $errorClassification);
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     * @param array $errorClassification
     */
    public function error_classification_should_contain_errorType(array $errorClassification): void
    {
        $this->markTestSkipped("MGPG errorClassfification not supported yet.");
        $this->assertArrayHasKey('errorType', $errorClassification);
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     * @param array $errorClassification
     */
    public function error_classification_should_contain_groupMessage(array $errorClassification): void
    {
        $this->markTestSkipped("MGPG errorClassfification not supported yet.");
        $this->assertArrayHasKey('groupMessage', $errorClassification);
    }

    /**
     * @test
     * @depends it_should_have_errorClassification_in_the_response
     * @param array $errorClassification Error classification.
     * @return void
     */
    public function error_classification_should_contain_recommendedAction(array $errorClassification): void
    {
        $this->markTestSkipped("MGPG errorClassfification not supported yet.");
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

        // MGPG does not support errorClassification yet.
        // $responsePurchase = json_decode($purchaseDeclined->response->getContent(), true);
        // $this->assertArrayHasKey('errorClassification', $responsePurchase);
    }

    /**
     * @return array
     */
    private function buildProcessPurchasePayload(): array
    {
        return [
            'siteId'  => ProcessPurchaseBase::TESTING_SITE,
            'member'  => [
                'email'       => $this->faker->email,
                'username'    => 'testPurchasegateway',
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'US',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'US',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'type'                => 'cc',
                'method'              => 'master',
                'ccNumber'            => $this->faker->creditCardNumber('MasterCard'),
                'cvv'                 => '123',
                'cardExpirationMonth' => '05',
                'cardExpirationYear'  => '2023'
            ]
        ];
    }
}
