<?php

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\ExistingMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithSubscriptionIdTest extends ProcessPurchaseBase
{
    public const SUBSCRIPTION_ID = '7a46dda4-e6af-47db-9ebc-b22536e9cd8e';
    public const MEMBER_ID       = 'fccacc3d-6b33-4c73-8da4-ef6f510dd1bf';

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_with_subscription_id_should_contain_x_auth_token(): string
    {
        $data['subscriptionId'] = self::SUBSCRIPTION_ID;
        $data['memberId']       = self::MEMBER_ID;

        $response               = $this->initExistingMemberWithSubscriptionId($data, true);
        $response->seeHeader('X-Auth-Token');

        $responseJson = json_decode($this->response->getContent(), true);
        $this->assertEquals(FraudRecommendation::NO_ACTION, $responseJson['fraudRecommendation']['code'], self::RETURNING_NOT_EXPECTED_FRAUD);

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_with_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success($token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processExistingMemberPurchasePayload(
                [
                    'member' => [
                        'username'    => 'testPurchase' . random_int(100, 999),
                        'password'    => 'test12345',
                        'firstName'   => $this->faker->firstName,
                        'lastName'    => $this->faker->lastName,
                        'countryCode' => self::COUNTRY_CODE_NO_FRAUD,
                        'zipCode'     => 'h1h1h1',
                        'address1'    => '123 Random Street',
                        'address2'    => 'Hello Boulevard',
                        'city'        => 'Montreal',
                        'state'       => 'CA',
                        'phone'       => '514-000-0911',
                        'email'       => $this->faker->email
                    ]
                ]
            ),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);
        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_key(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_with_true_value(array $response): void
    {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_purchase_id_key(array $response): void
    {
        $this->assertArrayHasKey('purchaseId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_member_id_key(array $response): void
    {
        $this->assertArrayHasKey('memberId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_the_correct_member_id(array $response): void
    {
        $this->assertEquals(self::MEMBER_ID, $response['memberId']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_bundle_id_key(array $response): void
    {
        $this->assertArrayHasKey('bundleId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_addon_id_key(array $response): void
    {
        $this->assertArrayHasKey('addonId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_subscription_id_key(array $response): void
    {
        $this->assertArrayHasKey('subscriptionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_the_correct_subscription_id(array $response): void
    {
        $this->assertEquals(self::SUBSCRIPTION_ID, $response['subscriptionId']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_transactionId_key(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_billerName_key(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_digest_key(array $response): void
    {
        $this->assertArrayHasKey('digest', $response);
    }

    /**
     * When an existing member purchases with new card and a different username than the existing one,
     * isUsernamePadded should always be set to false as the user cannot change the subscription's username.
     *
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_isUsernamePadded_set_to_false(array $response): void
    {
        $this->assertArrayHasKey('isUsernamePadded', $response);
        $this->assertFalse($response['isUsernamePadded']);
    }

    /**
     * @test
     * @depends purchase_initiating_with_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function second_process_purchase_should_return_session_expired($token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($token)
        );

        $response->seeJsonContains(['code' => 101]);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function second_process_purchase_should_return_token_expired_with_proper_error_code(): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($this->getJwtToken())
        );

        $payload = json_decode($response->response->getContent(), true);

        $this->assertEquals(
            [
                'code'  => Code::TOKEN_EXPIRED,
                'error' => Code::getMessage(Code::TOKEN_EXPIRED)
            ],
            $payload
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_complete_successfully_a_declined_NSF_transaction_for_main_purchase_without_xSell()
    {
        $payload                                       = $this->initExistingMemberWithSubscriptionIdPayload();
        $payload['subscriptionId']                     = self::SUBSCRIPTION_ID;
        $payload['memberId']                           = self::MEMBER_ID;
        $payload['currency']                           = "USD";
        $payload['amount']                             = 0.02;
        $payload['rebillAmount']                       = 0.02;
        $payload['tax']['initialAmount']['afterTaxes'] = 0.02;
        $payload['tax']['rebillAmount']['afterTaxes']  = 0.02;

        $headers                    = $this->initPurchaseHeaders();
        $headers['X-Force-Cascade'] = 'test-rocketgate';

        // init secondary revenue purchase
        $responseInit = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );
        $responseInit->seeHeader('X-Auth-Token');

        // process secondary revenue purchase
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
     * @depends it_should_complete_successfully_a_declined_NSF_transaction_for_main_purchase_without_xSell
     *
     * @param array $response Purchase Response.
     *
     * @return void
     */
    public function it_should_contain_correct_response_for_NSF_transaction_on_purchase_without_xSell(array $response): void
    {
        $this->assertArrayHasKey('isNsf', $response);
        $this->assertTrue($response['isNsf']);
        $this->assertFalse($response['success']);
        $this->assertEquals(FinishProcess::TYPE, $response['nextAction']['type']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_complete_successfully_a_declined_NSF_transaction_for_main_purchase_with_xSell()
    {
        $payload                                                              = $this->initExistingMemberWithSubscriptionIdPayload();
        $payload['subscriptionId']                                            = self::SUBSCRIPTION_ID;
        $payload['memberId']                                                  = self::MEMBER_ID;
        $payload['currency']                                                  = "USD";
        $payload['crossSellOptions'][0]['amount']                             = 0.02;
        $payload['crossSellOptions'][0]['rebillAmount']                       = 0.02;
        $payload['crossSellOptions'][0]['tax']['initialAmount']['afterTaxes'] = 0.02;
        $payload['crossSellOptions'][0]['tax']['rebillAmount']['afterTaxes']  = 0.02;

        $headers                    = $this->initPurchaseHeaders();
        $headers['X-Force-Cascade'] = 'test-rocketgate';

        // init secondary revenue purchase
        $responseInit = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );
        $responseInit->seeHeader('X-Auth-Token');

        // process secondary revenue purchase
        $purchaseDeclined = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithOneSelectedCrossSale(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $purchaseDeclined->assertResponseStatus(Response::HTTP_OK);

        return json_decode($purchaseDeclined->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_complete_successfully_a_declined_NSF_transaction_for_main_purchase_with_xSell
     *
     * @param array $response Purchase Response.
     *
     * @return void
     */
    public function it_should_contain_correct_response_for_NSF_transaction_on_purchase_with_xSell(array $response): void
    {
        // assertion for main purchase successful
        $this->assertTrue($response['success']);
        $this->assertEquals(FinishProcess::TYPE, $response['nextAction']['type']);

        // assertion for xSell purchase declined NSF
        $xSell = $response['crossSells'][0];
        $this->assertArrayHasKey('isNsf', $xSell);
        $this->assertTrue($xSell['isNsf']);
        $this->assertFalse($xSell['success']);
    }
}
