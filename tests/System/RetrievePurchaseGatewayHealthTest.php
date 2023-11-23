<?php
declare(strict_types=1);

namespace Tests\System;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\PurchaseGatewayHealthHttpDTO;
use Tests\SystemTestCase;

class RetrievePurchaseGatewayHealthTest extends SystemTestCase
{
    /**
     * @return string
     */
    private function validRequestUri(): string
    {
        return "/api/v1/healthCheck";
    }

    /**
     * @return string
     */
    private function validRequestUriWithPostbackStatus(): string
    {
        return "/api/v1/healthCheck?postbackStatus=true";
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_success_for_purchase_gateway_health_check(): array
    {
        $response = $this->json('GET', $this->validRequestUri());
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_for_purchase_gateway_health_check
     * @param array $response Data
     * @return array
     */
    public function it_should_return_an_array_for_purchase_gateway_health_check(array $response): array
    {
        $this->assertIsArray($response);
        return $response;
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_status_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey('status', $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_number_of_site_configurations_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey('number of site configurations', $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_fraud_advice_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::FRAUD_ADVICE_SERVICE_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_cascade_service_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::CASCADE_SERVICE_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_biller_mapping_service_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::BILLER_MAPPING_SERVICE_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_email_service_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::EMAIL_SERVICE_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_transaction_service_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::TRANSACTION_SERVICE_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_payment_template_service_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::PAYMENT_TEMPLATE_SERVICE_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_member_profile_gateway_communication_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::MEMBER_PROFILE_GATEWAY_COMMUNICATION, $response);
    }

    /**
     * @test
     * @depends it_should_return_an_array_for_purchase_gateway_health_check
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_bundle_projection_status_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey(PurchaseGatewayHealthHttpDTO::BUNDLE_PROJECTION_STATUS, $response);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_success_for_purchase_gateway_health_with_postback_status(): array
    {
        $response = $this->json('GET', $this->validRequestUriWithPostbackStatus());
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_for_purchase_gateway_health_with_postback_status
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_number_of_failed_jobs_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey('number of failed postback jobs since last cleanup', $response);
    }

    /**
     * @test
     * @depends it_should_return_success_for_purchase_gateway_health_with_postback_status
     * @param array $response Data
     * @return void
     */
    public function it_should_return_the_postback_queue_length_key_inside_the_response_array(array $response): void
    {
        $this->assertArrayHasKey('number of postback jobs in queue', $response);
    }
}
