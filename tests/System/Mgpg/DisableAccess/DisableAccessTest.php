<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\DisableAccess;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class DisableAccessTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_should_be_success_with_netbilling_biller(): array
    {
        $initResponse = $this->initPurchaseProcessWithOneCrossSaleWithNetbilling(true);
        $initResponse->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');

        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedForNetbillingBiller();

        $this->override(
            [
                "fraudService"        => [
                    "callProcessCustomer" => [
                        [
                            "severity" => "Allow",
                            "code"     => 1000,
                            "message"  => "Allow"
                        ]
                    ]
                ],
                "cachedConfigService" => [
                    "getAllBillerConfigs" => [
                        [
                            "name"           => "netbilling",
                            "type"           => 0,
                            "supports3DS"    => false,
                            "isLegacyBiller" => false,
                            "sendAllCharges" => false,
                            "createdAt"      => null,
                            "updatedAt"      => null
                        ]
                    ]
                ]
            ]
        );
        sleep(3);
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $processResponse = json_decode($response->response->getContent(), true);

        $this->assertArrayHasKey('sessionId', $processResponse);
        $this->assertArrayHasKey('success', $processResponse);
        $this->assertTrue($processResponse['success']);
        $this->assertArrayHasKey('memberId', $processResponse);
        $this->assertArrayHasKey('transactionId', $processResponse);

        return $processResponse;
    }

    /**
     * @test
     * @depends purchase_should_be_success_with_netbilling_biller
     *
     * @param array $processResponse
     */
    public function disable_operation_should_be_successful(array $processResponse): void
    {
        $header = [
            'Content-Type' => 'application/json',
            'X-CORRELATION-ID' => '23dbca8f-0323-404b-97c7-e763d9af8819'
        ];

        $cancelData = [
            'businessGroupId'    => ProcessPurchaseBase::PAYSITES_BUSINESS_GROUP_ID,
            'memberId'           => $processResponse['memberId'],
            'itemId'             => $processResponse['transactionId'],
            'siteId'             => ProcessPurchaseBase::REALITY_KINGS_SITE_ID,
            'usingMemberProfile' => false
        ];

        $response = $this->json(
            'POST',
            $this->validDisableAccessRequestUri(),
            $cancelData,
            $header
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $cancelRebillResponse = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('sessionId', $cancelRebillResponse);
        $this->assertArrayHasKey('correlationId', $cancelRebillResponse);
        $this->assertArrayHasKey('nextAction', $cancelRebillResponse);
        $this->assertArrayHasKey('invoice', $cancelRebillResponse);
        $this->assertSame($cancelRebillResponse['invoice']['status'], 'success');
        $this->assertSame($cancelRebillResponse['invoice']['isDisabled'], true);
        $this->assertArrayHasKey('memberId', $cancelRebillResponse['invoice']);
        $this->assertArrayHasKey('transactionId', $cancelRebillResponse['invoice']);
    }

    /**
     * @test
     * @depends purchase_should_be_success_with_netbilling_biller
     *
     * @param array $processResponse
     */
    public function disable_operation_should_be_declined(array $processResponse): void
    {
        $header = [
            'Content-Type' => 'application/json',
            'X-CORRELATION-ID' => '23dbca8f-0323-404b-97c7-e763d9af8819'
        ];

        $cancelData = [
            'businessGroupId'    => ProcessPurchaseBase::PAYSITES_BUSINESS_GROUP_ID,
            'memberId'           => $processResponse['memberId'],
            'itemId'             => $processResponse['transactionId'],
            'siteId'             => ProcessPurchaseBase::REALITY_KINGS_SITE_ID,
            'usingMemberProfile' => false
        ];

        $response = $this->json(
            'POST',
            $this->validDisableAccessRequestUri(),
            $cancelData,
            $header
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $cancelRebillResponse = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('sessionId', $cancelRebillResponse);
        $this->assertArrayHasKey('correlationId', $cancelRebillResponse);
        $this->assertArrayHasKey('nextAction', $cancelRebillResponse);
        $this->assertArrayHasKey('invoice', $cancelRebillResponse);
        $this->assertSame($cancelRebillResponse['invoice']['status'], 'decline');
        $this->assertSame($cancelRebillResponse['invoice']['isDisabled'], true);
        $this->assertArrayHasKey('memberId', $cancelRebillResponse['invoice']);
        $this->assertArrayHasKey('transactionId', $cancelRebillResponse['invoice']);
    }

    /**
     * @return string
     */
    protected function validDisableAccessRequestUri(): string
    {
        return '/mgpg/api/v1/disable-access';
    }
}