<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProbillerMGPG\Common\PaymentMethod;
use ProBillerNG\PurchaseGateway\Code;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false, self::TESTING_SITE);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function purchase_initiating_should_return_failures_when_using_excessive_initialDays(): void
    {
        $response = $this->initPurchaseProcessWithOneCrossSaleAndExcessiveInitialDays(false, self::TESTING_SITE);
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success($token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     *
     * @param string $token Token.
     * @return void
     */
    public function process_purchase_should_return_fail_when_using_member_name_with_number($token): void
    {
        //TODO find out if Mgpg returns fail in case if member name contains number
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadNameWithNumbers(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_session_id(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
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
    public function process_purchase_response_should_contain_purchaseId_key(array $response): void
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
    public function process_purchase_response_should_contain_memberId_key(array $response): void
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
    public function process_purchase_response_should_contain_bundleId_key(array $response): void
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
    public function process_purchase_response_should_contain_addonId_key(array $response): void
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
    public function process_purchase_response_should_contain_subscriptionId_key(array $response): void
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
    public function adapter_process_purchase_response_should_not_contain_billerName_key(array $response): void
    {
        $this->assertArrayNotHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_not_contain_errorClassification_key(array $response): void
    {
        $this->assertArrayNotHasKey('errorClassification', $response);
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
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
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
     * @return void
     * @throws \Exception
     */
    public function process_purchase_should_return_success_when_using_member_data_with_spaces(): void
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false, self::TESTING_SITE);
        $response->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithSpaces(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_minimum_user_info()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_missing_tax_information()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_incomplete_tax_information()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_missing_site_id_on_cross_sales_tax_information()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @dataProvider paymentMethodsProvider
     * @param string $paymentMethod
     * @param string $creditCard
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_with_new_payment_methods(
        string $paymentMethod,
        string $creditCard
    ): array {
        $response = $this->initPurchaseProcessWithOneCrossSale(
            true,
            ProcessPurchaseBase::TESTING_SITE,
            ['paymentMethod' => $paymentMethod]
        );
        $response->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');

        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale(
            ProcessPurchaseBase::TESTING_SITE
        );

        $processPayload['payment']['ccNumber'] = $creditCard;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @return array
     */
    public function paymentMethodsProvider(): array
    {
        return [
            'MIR'        => [PaymentMethod::MIR, '2202034292937613'],
            'CCUNIONPAY' => [PaymentMethod::CCUNIONPAY, '8128250938788263']
        ];
    }
}
