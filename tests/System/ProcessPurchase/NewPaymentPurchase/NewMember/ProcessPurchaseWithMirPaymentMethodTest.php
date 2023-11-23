<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class ProcessPurchaseWithMirPaymentMethodTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token_for_mir_payment_method_purchase(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false, ProcessPurchaseBase::TESTING_SITE, 'mir');

        $responseJson = json_decode($this->response->getContent(), true);
        $this->assertEquals(FraudRecommendation::NO_ACTION, $responseJson['fraudRecommendation']['code'], self::RETURNING_NOT_EXPECTED_FRAUD);

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token_for_mir_payment_method_purchase
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_for_mir_payment_method($token): array
    {
        $requestPayload = $this->processPurchasePayloadWithNoSelectedCrossSale(ProcessPurchaseBase::TESTING_SITE);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $requestPayload,
            $this->processPurchaseHeaders($token)
        );

        $responseJson = json_decode($this->response->getContent(), true);

        $response->assertResponseStatus(Response::HTTP_OK);

        return $responseJson;
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
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
     * @depends process_purchase_should_return_success_for_mir_payment_method
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_with_true_value_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_purchaseId_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayHasKey('purchaseId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_memberId_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayHasKey('memberId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_biller_name_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_biller_name_as_rocketgate_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertSame('rocketgate', strtolower($response['billerName']));
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_subscriptionId_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayHasKey('subscriptionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_transactionId_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_billerName_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_mir_payment_method
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_not_contain_errorClassification_key_for_mir_payment_method_purchase(array $response): void
    {
        $this->assertArrayNotHasKey('errorClassification', $response);
    }
}