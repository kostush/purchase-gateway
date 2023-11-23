<?php

declare(strict_types=1);

namespace Tests\System\Mgpg\ValidateCaptcha;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class ValidateCaptchaTest extends ProcessPurchaseBase
{
    /**
     * @var string
     */
    private $uri = '/mgpg/api/v1/purchase/validate-captcha/';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param bool $triggerCaptcha
     * @return mixed Purchase Init Post Response
     * @throws \Exception
     */
    private function purchaseInit(bool $triggerCaptcha = false)
    {
        if($triggerCaptcha) {
            $this->override([
                    "fraudService" => [
                        "callInitVisitor" => [
                            [
                                "severity" => "Action",
                                "code"     => 200,
                                "message"  => "Show_Captcha"
                            ]
                        ]
                    ]
                ]
            );
        }

        $response = $this->initPurchaseProcessWithOneCrossSale(
            false,
            ProcessPurchaseBase::REALITY_KINGS_SITE_ID
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_success_200_for_init(): void
    {
        $response = $this->purchaseInit(true);

        $payload = ['siteId' => ProcessPurchaseBase::REALITY_KINGS_SITE_ID];

        $headers = [
            'Authorization' => 'Bearer ' . (string) $response->response->headers->get('x-auth-token'),
            'X-Api-Key'     => $this->paysitesXApiKey()
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
        $response = $this->purchaseInit();

        $token   = (string) $response->response->headers->get('X-Auth-Token');
        $payload = ['siteId' => ProcessPurchaseBase::REALITY_KINGS_SITE_ID];
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Api-Key'     => $this->paysitesXApiKey()
        ];

        $this->override([
                "fraudService" => [
                    "callProcessCustomer" => [
                        [
                            "severity" => "Action",
                            "code"     => 200,
                            "message"  => "Show_Captcha"
                        ]
                    ]
                ]
            ]
        );

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $json = json_decode($response->response->getContent(), true);

        $this->assertEquals('Show_Captcha', $json['fraudRecommendation']['message'] ?? null);

        $response = $this->json('POST', $this->uri . 'process', $payload, $headers);

        $response->assertResponseStatus(Response::HTTP_OK);
    }
}
