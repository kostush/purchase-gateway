<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\Refund;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

/**
 * Class RefundTest
 * @package Tests\System\Mgpg\Refund
 */
class RefundTest extends ProcessPurchaseBase
{
    public const PURCHASED_AMOUNT = 29.99;

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_with_netbilling_biller(): array
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
     * @depends process_purchase_should_return_success_with_netbilling_biller
     *
     * @param array $processResponse
     */
    public function refund_operation_should_be_successful_with_netbilling_billing_with_partial_amount(array $processResponse): void
    {
        $header = [
            'Content-Type' => 'application/json',
            'X-CORRELATION-ID' => '23dbca8f-0323-404b-97c7-e763d9af8819'
        ];

        $refundData = [
            'businessGroupId' => ProcessPurchaseBase::PAYSITES_BUSINESS_GROUP_ID,
            'memberId'        => $processResponse['memberId'],
            'itemId'          => $processResponse['transactionId'],
            'siteId'          => ProcessPurchaseBase::REALITY_KINGS_SITE_ID,
            'amount'          => self::PURCHASED_AMOUNT - 19.00,
            'reason'          => "The reason for refund it test"
        ];

        $response = $this->json(
            'POST',
            $this->validRefundRequestUri(),
            $refundData,
            $header
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $refundResponse = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('sessionId', $refundResponse);
        $this->assertArrayHasKey('correlationId', $refundResponse);
        $this->assertArrayHasKey('nextAction', $refundResponse);
        $this->assertArrayHasKey('invoice', $refundResponse);
        $this->assertSame($refundResponse['invoice']['status'], 'success');
        $this->assertArrayHasKey('memberId', $refundResponse['invoice']);
        $this->assertArrayHasKey('transactionId', $refundResponse['invoice']);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_successful_with_rocketgate_biller(): array
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(
            false,
            self::TESTING_SITE,
            ['amount' => self::INITIAL_AMOUNT]
        );
        $response->seeHeader('X-Auth-Token');

        $token = (string) $this->response->headers->get('X-Auth-Token');;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE),
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
     * Note: RC does not support same day refund in non-production environment
     * @test
     * @depends process_purchase_should_return_successful_with_rocketgate_biller
     *
     * @param array $processResponse
     */
    public function refund_operation_should_be_declined_with_rocketgate_billing_with_partial_amount(array $processResponse
    ): void {
        $header = [
            'Content-Type' => 'application/json'
        ];

        $refundData = [
            'businessGroupId' => ProcessPurchaseBase::TESTING_BUSINESS_GROUP_ID,
            'memberId'        => $processResponse['memberId'],
            'itemId'          => $processResponse['transactionId'],
            'siteId'          => ProcessPurchaseBase::TESTING_SITE,
            'amount'          => self::PURCHASED_AMOUNT - 19.00,
            'reason'          => "The reason for refund it test"
        ];

        $response = $this->json(
            'POST',
            $this->validRefundRequestUri(),
            $refundData,
            $header
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $refundResponse = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('sessionId', $refundResponse);
        $this->assertArrayHasKey('correlationId', $refundResponse);
        $this->assertArrayHasKey('nextAction', $refundResponse);
        $this->assertArrayHasKey('invoice', $refundResponse);
        $this->assertSame($refundResponse['invoice']['status'], 'decline');
        $this->assertArrayHasKey('code', $refundResponse['invoice']);
        $this->assertNotNull($refundResponse['invoice']['code']);
        $this->assertArrayHasKey('memberId', $refundResponse['invoice']);
        $this->assertArrayHasKey('transactionId', $refundResponse['invoice']);
    }

    /**
     * Note: RC does not support same day refund in non-production environment
     * @test
     * @depends process_purchase_should_return_successful_with_rocketgate_biller
     *
     * @param array $processResponse
     */
    public function refund_operation_should_be_successful_with_rocketgate_billing_with_full_amount(array $processResponse
    ): void {
        $header = [
            'Content-Type' => 'application/json'
        ];

        $refundData = [
            'businessGroupId' => ProcessPurchaseBase::TESTING_BUSINESS_GROUP_ID,
            'memberId'        => $processResponse['memberId'],
            'itemId'          => $processResponse['transactionId'],
            'siteId'          => ProcessPurchaseBase::TESTING_SITE,
            'amount'          => self::PURCHASED_AMOUNT - 19.00,
            'reason'          => "The reason for refund it test"
        ];

        $response = $this->json(
            'POST',
            $this->validRefundRequestUri(),
            $refundData,
            $header
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $refundResponse = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('sessionId', $refundResponse);
        $this->assertArrayHasKey('correlationId', $refundResponse);
        $this->assertArrayHasKey('nextAction', $refundResponse);
        $this->assertArrayHasKey('invoice', $refundResponse);
        $this->assertSame($refundResponse['invoice']['status'], 'decline');
        $this->assertArrayHasKey('code', $refundResponse['invoice']);
        $this->assertNotNull($refundResponse['invoice']['code']);
        $this->assertArrayHasKey('memberId', $refundResponse['invoice']);
        $this->assertArrayHasKey('transactionId', $refundResponse['invoice']);
    }

    /**
     * @return string
     */
    protected function validRefundRequestUri(): string
    {
        return '/mgpg/api/v1/refund';
    }
}