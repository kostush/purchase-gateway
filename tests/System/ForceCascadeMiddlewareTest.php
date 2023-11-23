<?php

namespace Tests\System;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;
use Tests\System\InitPurchase\InitPurchase;

class ForceCascadeMiddlewareTest extends InitPurchase
{
    /**
     * @test
     * @return void
     */
    public function purchase_initiating_should_return_success_when_force_cascade_is_netbilling(): void
    {
        $this->payload['clientIp']          = '10.10.10.10';
        $this->payload['currency']          = 'USD';
        $this->payload['clientCountryCode'] = 'BR';
        $header                             = $this->header();
        $header['X-Force-Cascade']          = RetrieveCascadeTranslatingService::TEST_NETBILLING;
        $response                           = $this->json('POST', $this->validRequestUri(), $this->payload, $header);
        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @test
     * @return void
     */
    public function purchase_initiating_should_return_success_when_force_cascade_is_rocketgate(): void
    {
        $header                    = $this->header();
        $header['X-Force-Cascade'] = RetrieveCascadeTranslatingService::TEST_ROCKETGATE;
        $response                  = $this->json('POST', $this->validRequestUri(), $this->payload, $header);
        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @test
     * @return void
     */
    public function purchase_initiating_should_return_bad_request_when_force_cascade_is_invalid(): void
    {
        $header                    = $this->header();
        $header['X-Force-Cascade'] = 'InvalidForceCascade';
        $response                  = $this->json('POST', $this->validRequestUri(), $this->payload, $header);
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }
}
