<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBaseForChequePaymentType;

class ProcessPurchaseWithChequePaymentTypeTest extends ProcessPurchaseBaseForChequePaymentType
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token_for_cheque_purchase(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSaleForChequePurchase(
            true,
            ProcessPurchaseBase::TESTING_SITE
        );

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token_for_cheque_purchase
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_for_cheque_payment_type($token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSaleForChequePaymentType(ProcessPurchaseBase::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_responscontain_success_with_true_valuee_should_contain_session_id(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_with_true_value_for_cheque_purchase(array $response): void
    {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_purchaseId_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('purchaseId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_memberId_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('memberId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
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
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_subscriptionId_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('subscriptionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_transactionId_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     * @param array $response Response.
     *
     * @return void
     */
    public function adapter_process_purchase_response_should_contain_billerName_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayNotHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_not_contain_errorClassification_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayNotHasKey('errorClassification', $response);
    }

    /**
     * @test
     * @dataProvider checkInformationProvider
     * @param array $data
     * @throws \Exception
     */
    public function it_should_return_bad_request_with_invalid_check_information(array $data): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchaseCheckPayload($data),
            $this->processPurchaseHeaders("token")
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return array
     */
    public function checkInformationProvider(): array
    {
        return [
            "boll_account_number"         => [["accountNumber" => true]],
            "boll_routing_number"         => [["routingNumber" => true]],
            "int_saving_account"          => [["savingAccount" => 3]],
            "string_saving_account"       => [["savingAccount" => "string"]],
            "empty_saving_account"        => [["savingAccount" => ""]],
            "string_social_security"      => [["socialSecurityLast4" => "string"]],
            "empty_social_security"       => [["socialSecurityLast4" => ""]],
            "more_than_5_social_security" => [["socialSecurityLast4" => "12345"]],
            "less_than_social_security"   => [["socialSecurityLast4" => "123"]],
            "empty_label"                 => [["label" => ""]],
            "bool_label"                  => [["label" => true]],
        ];
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function processPurchaseCheckPayload(array $data = []): array
    {
        $username = 'testPurchase' . random_int(100, 999);

        return [
            'siteId'  => 'a2d4f06f-afc8-41c9-9910-0302bd2d9531',
            'member'  => [
                'email'       => $username . '@test.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => 'Mister',
                'lastName'    => 'Axe',
                'countryCode' => 'US',
                'zipCode'     => '89141',
                'address1'    => '123 Main St',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Las Vegas',
                'state'       => 'NV',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                "checkInformation" => [
                    "routingNumber"       => $data["routingNumber"] ?? $this->faker->numberBetween(10000000, 999999999),
                    "accountNumber"       => $data["accountNumber"] ?? "112233",
                    "savingAccount"       => $data["savingAccount"] ?? false,
                    "socialSecurityLast4" => $data["socialSecurityLast4"] ?? "5233",
                    "label"               => $data["label"] ?? "label"
                ]
            ]
        ];
    }

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token_for_cheque_purchase_Again(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSaleForChequePurchase(
            true,
            ProcessPurchaseBase::TESTING_SITE
        );

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token_for_cheque_purchase_Again
     * @dataProvider checkSavingAccountProvider
     * @param array $data
     * @param string $token Token.
     * @throws \Exception
     */
    public function it_should_return_OK_request_with_savingAccount_string_type_check_information(array $data, string $token): void
    {

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchaseCheckPayload($data),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @return array
     */
    public function checkSavingAccountProvider(): array
    {
        return [
            "string_saving_account" => [["savingAccount" => "string"]],
        ];
    }
}
