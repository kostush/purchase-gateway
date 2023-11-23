<?php
declare(strict_types=1);

namespace Tests\System;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class RetrieveFailedBillersTest extends ProcessPurchaseBase
{
    /**
     * @return string
     */
    private function validRequestUri(): string
    {
        return '/api/v1/failedBillers/session';
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_a_billers_array_for_a_failed_purchase_session(): array
    {
        $session = $this->performTwoDeclinedPurchases(true);

        //get the failed billers for the retrieved session id
        $response = $this->json('GET', $this->validRequestUri() . '/' . $session);
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_a_billers_array_for_a_failed_purchase_session
     * @param array $response The endpoint response
     * @return void
     */
    public function failed_billers_response_array_should_contain_rocketgate(array $response): void
    {
        $found = false;
        foreach ($response['failedBillers'] as $key => $biller) {
            if ($biller['billerName'] === RocketgateBiller::BILLER_NAME) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_404_request_response_for_incorrect_session_id(): void
    {
        $response = $this->json('GET', $this->validRequestUri() . '/123');
        $response->assertResponseStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_not_found_response_for_non_existent_session_id(): void
    {
        $response = $this->json('GET', $this->validRequestUri() . '/' .  $this->faker->uuid);
        $response->assertResponseStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function is_should_return_a_bad_request_response_for_a_successful_purchase_session(): void
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false);
        $content  = json_decode($this->response->getContent(), true);
        $response->seeHeader('X-Auth-Token');

        $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $this->json('GET', $this->validRequestUri() . '/' . $content['sessionId']);

        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }
}
