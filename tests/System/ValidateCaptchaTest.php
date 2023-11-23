<?php

declare(strict_types=1);

namespace System;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class ValidateCaptchaTest extends ProcessPurchaseBase
{
    /**
     * @var ResponseHeaderBag
     */
    private $initHeaders;

    /**
     * @var string
     */
    private $uri = '/api/v1/purchase/validate-captcha/';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $initCallResponse = $this->initPurchaseProcessWithOneCrossSale(
            false,
            ProcessPurchaseBase::REALITY_KINGS_SITE_ID
        );

        $this->initHeaders = $initCallResponse->response->headers;
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_success_200_for_init(): void
    {
        $payload = ['siteId' => ProcessPurchaseBase::REALITY_KINGS_SITE_ID];

        $headers = [
            'Authorization' => 'Bearer ' . (string) $this->initHeaders->get('x-auth-token'),
            'x-api-key'     => $this->paysitesXApiKey()
        ];

        $response = $this->json('POST', $this->uri . 'init', $payload, $headers);

        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_success_200_for_process(): void
    {
        $payload = ['siteId' => ProcessPurchaseBase::REALITY_KINGS_SITE_ID];

        $token = (string) $this->initHeaders->get('x-auth-token');

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'x-api-key'     => $this->paysitesXApiKey()
        ];

        $this->json('POST', $this->uri . 'init', $payload, $headers);

        $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithProcessCaptchaAdvised(),
            $this->processPurchaseHeaders($token)
        );

        $response = $this->json('POST', $this->uri . 'process', $payload, $headers);

        $response->assertResponseStatus(Response::HTTP_OK);
    }
}
