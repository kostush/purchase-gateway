<?php

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\ExistingMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Code;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithoutSubscriptionIdTest extends ProcessPurchaseBase
{
    public const MEMBER_ID = '7a46dda4-e6af-47db-9ebc-b22536e9cd8e';

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_without_subscription_id_should_contain_x_auth_token(): string
    {
        $data['memberId'] = self::MEMBER_ID;
        $response         = $this->initExistingMemberWithoutSubscriptionId($data, true);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_without_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_should_return_error_when_username_or_password_not_in_process_payload($token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processExistingMemberPurchasePayload(),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @depends purchase_initiating_without_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_when_username_and_password_are_in_process_payload($token): array
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
                        'countryCode' => 'CA',
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
     *
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
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function adapter_process_purchase_response_should_not_contain_billerName_key(array $response): void
    {
        $this->assertArrayNotHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_when_username_and_password_are_in_process_payload
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_digest_key(array $response): void
    {
        $this->assertArrayHasKey('digest', $response);
    }

    /**
     * @test
     * @depends purchase_initiating_without_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function second_process_purchase_using_same_success_token_should_return_error(string $token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
        $response->seeJsonContains(['code' => self::INCORRECT_SAGA_STEP_PROCESS]);
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
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE),
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
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_when_member_info_is_not_in_process_payload(): array
    {
        $data['memberId'] = self::MEMBER_ID;
        $this->initExistingMemberWithoutSubscriptionId($data, true);

        $response = json_decode($this->response->getContent(), true);

        $processPurchasePayloadWithOutMemberInfo = [
            'siteId'  => self::TESTING_SITE,
            'payment' => [
                'paymentTemplateInformation' => [
                    'paymentTemplateId' => $response['paymentTemplateInfo'][0]['templateId']
                ]
            ]
        ];
        $response                                = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayloadWithOutMemberInfo,
            $this->processPurchaseHeaders($this->response->headers->get('X-Auth-Token'))
        );
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }
}
