<?php

declare(strict_types=1);

namespace Tests\System\Mgpg\ThreeD;

use Illuminate\Http\Response;
use ProbillerMGPG\Purchase\Common\NextAction;
use ProbillerMGPG\Purchase\Common\ThreeD;
use ProbillerMGPG\Purchase\Complete3ds\Complete3dsResponse;
use ProbillerMGPG\Purchase\Process\PurchaseProcessResponse;
use ProbillerMGPG\Purchase\Process\Response\Charge;
use ProbillerMGPG\Purchase\Process\Response\Invoice;
use ProbillerMGPG\Purchase\Process\Response\Item;
use ProbillerMGPG\Purchase\Process\Response\PriceInfo;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

/**
 * Class ThreeDFlowTest
 * @package System
 * @group   common-fraud-service-integration
 */
class ThreeDFlowTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_proper_html_form_when_auth_url_token_properly_decoded(): string
    {
        $this->purchaseInit($this->buildDataToTrigger3dsV1());

        $response = $this->purchaseProcess(self::TESTING_SITE);

        $processResponse = json_decode($response->response->getContent(), true);

        $this->assertNotNull($processResponse['nextAction']['threeD']);

        $authUrl = $processResponse['nextAction']['threeD']['authenticateUrl'];

        $this->assertNotNull($authUrl);

        $authResult = $this->get($authUrl);

        $this->assertTrue(mb_stripos($authResult->response->getContent(), 'name="toBank"') !== false);

        return $authResult->response->getContent();
    }

    /**
     * @return mixed Purchase Init Post Response
     * @throws \Exception
     */
    private function purchaseInit(array $data = [])
    {
        $this->override(
            [
                "fraudService" => [
                    "callInitVisitor" => [
                        [
                            "severity" => "Action",
                            "code"     => 300,
                            "message"  => "Force_3DS"
                        ]
                    ]
                ],
                "cascade"      => [
                    "billers" => [
                        [
                            "rocketgate"
                        ]
                    ]
                ]
            ]);

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload($data['siteId'], $data),
            $this->initPurchaseHeaders($data['xApiKey'])
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response;
    }

    /**
     * @param string $siteId
     * @return mixed Purchase Init Post Response
     * @throws \Exception
     */
    private function purchaseProcess(string $siteId)
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale($siteId),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return $response;
    }

    /**
     * @test    Using Mocked Response as Complete 3D on MGPG has been unstable.
     * @depends it_should_return_proper_html_form_when_auth_url_token_properly_decoded
     * @throws \Exception
     */
    public function it_should_return_200_returning_from_auth_when_calling_complete(string $htmlAuthForm)
    {
        $completeData = $this->getHtmlData($htmlAuthForm);

        $service = \Mockery::mock(\ProbillerMGPG\ClientApi::class);
        $service
            ->shouldReceive('complete3ds')
            ->once()
            ->andReturn($this->createPurchaseProcessCompleteThreeDResponse());

        // Substitute injected client by the mocked one
        $this->app->instance(\ProbillerMGPG\ClientApi::class, $service);

        $completeCall = $this->json(
            'POST',
            $completeData['completeUri'],
            [
                'PaRes' => $completeData['PaRes']
            ]
        );

        $completeCall->assertResponseStatus(Response::HTTP_OK);

        return $completeCall->response->getContent();
    }

    /**
     * @param string $response Auth Response
     * @return array
     */
    private function getHtmlData(string $response): array
    {
        $data['PaRes']       = '';
        $data['completeUri'] = '';
        $data['success']     = '';
        $data['error']       = '';

        $doc = new \DOMDocument();
        $doc->loadHTML($response);
        $inputs = $doc->getElementsByTagName('input');
        foreach ($inputs as $input) {
            if ($input->getAttribute('name') == 'PaReq') {
                $data['PaRes'] = str_replace(
                    'PAREQ',
                    'PARES',
                    $input->getAttribute('value')
                );
            }
            if ($input->getAttribute('name') == 'TermUrl') {
                $data['completeUri'] = $input->getAttribute('value');
            }
            if ($input->getAttribute('name') == 'success') {
                $data['success'] = $input->getAttribute('value');
            }
            if ($input->getAttribute('name') == 'error') {
                $data['error'] = $input->getAttribute('value');
            }
        }

        return $data;
    }

    /**
     * Mocked Response for Complete Three D Call. This is part of what is expected when a call to complete three d is made.
     * The 2nd part being an HTML form, but it is not required for testing purposes.
     * @return Complete3dsResponse
     */
    protected function createPurchaseProcessCompleteThreeDResponse()
    {
        $response = new PurchaseProcessResponse();

        $response->invoice                                                 = new Invoice();
        $response->invoice->invoiceId                                      = $this->faker->uuid;
        $response->invoice->memberId                                       = $this->faker->uuid;
        $response->invoice->paymentId                                      = $this->faker->uuid;
        $response->invoice->redirectUrl                                    = "https://localhost:8008/mgpg/api/v1/return/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE2Mzc5NDgyMjYsIm5iZiI6MTYzNzk0ODIyNiwiZXhwIjoxNjM3OTUwMDI2LCJjbGllbnRVcmwiOiI2TkM1REhnOEM5dlZlUk03aktQaWlWMHJZQkxlV0ZOODF4VDVcL3ZxMGdHcnJyMDBjXC8rTWFMVktoazVDd2ErSmVQSnN6YmpXbzZubzlIMG9zN2pcL0FCeDNuSzBuWFVUalBHUE1FbzV1Wk15b3RMeHRHekZEXC93ZThwdzNzZ2o1VUVUaFwvZkNrUjNLdz09Iiwic2Vzc2lvbklkIjoiTksrNkZjcFVSdlhOV1NsczRGRE5NVmVzSmlWS0U1UmdLWE92MFhJRWdOT1JBTEVyK01BWXRYS0IxRkM2T0FZaTc1c1Qxa25KS1Zud1paamFuaDR1ZVlHNWZFOUhhN1VTV3VZdEZBPT0iLCJwdWJsaWNLZXlJZCI6ImxMSWhoVzdlb01GSCtWVDlEMjNiSUNGK3B4NjM0VERtR3cwd3NtVzM1R0pIUHY0bVF5cUJFMGc9IiwiY29ycmVsYXRpb25JZCI6ImsyaFwvTEppcUViY3BRVFJaMWk5TmdNWGNHV0lUTUVoV1Bra0Jkazd2N3FBM0Q2WWpNYTMyeW8wR0Rodjd3NHR6NXR4eGw4KzJkZlI3STlIclNQdFdFWVA1YXFRcXdvVUNRRjFXZlE9PSJ9.2Q0kS2GpJnKHg6xB9sCGuWhKB-Q0AUtNHgT1mApuTxHtd2PedZG7inuhkww4me-EvpDsc7jMDOLncjKqYd_brg";
        $response->invoice->charges                                        = [new Charge()];
        $response->invoice->charges[0]->businessTransactionOperation       = "subscriptionPurchase";
        $response->invoice->charges[0]->siteId                             = '018047dc-cbce-4de6-aec1-32260d793398';
        $response->invoice->charges[0]->chargeId                           = $this->faker->uuid;
        $response->invoice->charges[0]->isPrimaryCharge                    = true;
        $response->invoice->charges[0]->chargeDescription                  = "subscriptionPurchase";
        $response->invoice->charges[0]->transactionId                      = $this->faker->uuid;
        $response->invoice->charges[0]->isTrial                            = false;
        $response->invoice->charges[0]->items                              = [new Item()];
        $response->invoice->charges[0]->items[0]->skuId                    = $this->faker->uuid;
        $response->invoice->charges[0]->items[0]->displayName              = 'Brazzers';
        $response->invoice->charges[0]->items[0]->itemDescription          = 'Desc';
        $response->invoice->charges[0]->items[0]->quantity                 = 1;
        $response->invoice->charges[0]->items[0]->priceInfo                = new PriceInfo();
        $response->invoice->charges[0]->items[0]->priceInfo->basePrice     = 10;
        $response->invoice->charges[0]->items[0]->priceInfo->expiresInDays = 10;
        $response->invoice->charges[0]->items[0]->priceInfo->taxes         = 0;
        $response->invoice->charges[0]->items[0]->priceInfo->finalPrice    = 10;
        $response->invoice->charges[0]->items[0]->entitlements[]           = [
            'memberProfile' => [
                'data' => [
                    'addonId'        => $this->faker->uuid,
                    'subscriptionId' => $this->faker->uuid
                ]
            ]
        ];

        $response->nextAction             = new NextAction();
        $response->nextAction->threeD     = null;
        $response->nextAction->type       = 'finishProcess';
        $response->nextAction->resolution = 'server';
        $response->nextAction->reason     = 'CascadeBillersExhausted';

        $completeResponse                  = new Complete3dsResponse();
        $completeResponse->purchaseProcess = $response;

        return $completeResponse;
    }

    /**
     * @test
     * @depends it_should_return_200_returning_from_auth_when_calling_complete
     * @param string $completeResponse Complete Response
     * @return void
     */
    public function it_should_return_a_html_response(string $completeResponse): void
    {
        $this->assertTrue($this->isHTML($completeResponse));
    }

    /**
     * @param string $response Response
     * @return bool
     */
    private function isHTML(string $response): bool
    {
        return $response !== strip_tags($response);
    }

    protected function createPurchaseProcessAuthThreeDResponse()
    {
        $response                                      = new PurchaseProcessResponse();
        $response->nextAction                          = new NextAction();
        $response->nextAction->threeD                  = new \ProbillerMGPG\Purchase\Complete3ds\ThreeD();
        $response->nextAction->type                    = 'authenticate3D';
        $response->nextAction->threeD->authenticateUrl = "https://dev-secure.rocketgate.com/hostedpage/3DSimulator.jsp";
        $response->nextAction->threeD->termUrl         = "https://mgpg-api-2.dev.pbk8s.com/redirect/Complete3D/041b8659-7d08-48dc-8249-ca8982777aeb/eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJlbmNyeXB0ZWRKc29uIjoiaVA2OHR4SHhRVXM5UUg3clBYbG0vNHlYMmdlVHpGWEVBNzd2Y0xYalFWZ2c4TTlsK1BzNHdKZjd0akdyT0NOSVovbTl2UkJ6NmhMQ3I3dEsxbmN1Sys1ZXVmRUg5NU1pQjNCZGtmWDZDTUlRRFlVNzVYZ1UyZnN1ZXV4UXhkSWFGaVMxdWU3Q01VbmtPRGpmYnZUVUh4c0pUZ2VCODRoMklYcWovclpuOVBMdUptQ2l0Zy9taTFnQ2ZPZ2hJampVQ0tuaEI5eFlPemVZT0Y3bWVzQ2EzajllcjRDOFRyQ0hDNEJJTEMra09KQUpGUklocHp3TFlUOS9nWkhOY20wRk5HQ3BxYVM0bkFyaVNrbDRYUWQvZXpBTS8yd3VmMHVoMk9zUWduY1FVc3c9IiwibmJmIjoxNjE1MzAwOTQ0LCJleHAiOjE2NDY4MzY5NDQsImlzcyI6Imlzc3Vlcl9zYW1wbGUiLCJhdWQiOiJhdWRpZW5jZV9zYW1wbGUifQ.hhFGH5mVtUnLfbUplYwzN4DouVnTuYNniSoobqWzGOo";
        $response->nextAction->threeD->paReq           = "SimulatedPAREQ1000177CA15DBCD";

        return $response;
    }
}
