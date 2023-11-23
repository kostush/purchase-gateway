<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;
use Tests\System\ProcessPurchase\ProcessPurchaseBaseForChequePaymentType;

class ProcessPurchaseWithChequePaymentTypeTest extends ProcessPurchaseBaseForChequePaymentType
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token_for_cheque_purchase(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSaleForChequePurchase(true);

        $responseJson = json_decode($this->response->getContent(), true);
        $this->assertEquals(FraudRecommendation::NO_ACTION, $responseJson['fraudRecommendation']['code'], self::RETURNING_NOT_EXPECTED_FRAUD);

        sleep(10);
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
            $this->processPurchasePayloadWithNoSelectedCrossSaleForChequePaymentType(ProcessPurchaseBase::REALITY_KINGS_SITE_ID),
            $this->processPurchaseHeaders($token)
        );

        $responseJson = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('fraudRecommendation', $responseJson, self::RETURNING_NOT_EXPECTED_FRAUD);

        $response->assertResponseStatus(Response::HTTP_OK);

        return $responseJson;
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
    public function process_purchase_response_should_contain_biller_name_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_for_cheque_payment_type
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_biller_name_as_rocketgate_for_cheque_purchase(array $response): void
    {
        $this->assertSame('rocketgate', strtolower($response['billerName']));
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
    public function process_purchase_response_should_contain_billerName_key_for_cheque_purchase(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
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
}