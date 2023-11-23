<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\InitRebillUpdate;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class InitRebillUpdateTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_to_rebill_update_init(): void
    {
        $purchaseGatewayResponse = $this->makeAPurchase();
        $this->assertTrue($purchaseGatewayResponse['success'],'Failed Join Purchase.');
        $payload  = $this->initPayload($purchaseGatewayResponse['memberId'], $purchaseGatewayResponse['itemId']);
        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseOk();
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_fraud_on_rebill_update_init(): void
    {
        $purchaseGatewayResponse = $this->makeAPurchase();

        $this->assertTrue($purchaseGatewayResponse['success'],'Failed Join Purchase.');
        $payload  = $this->initPayloadForcingFraud($purchaseGatewayResponse['memberId'], $purchaseGatewayResponse['itemId']);
        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseOk();

        $responseArray = json_decode($this->response->getContent(), true);
        $this->assertTrue($responseArray['fraudAdvice']['blacklist']);
        $this->assertEquals(FraudRecommendation::BLACKLIST ,$responseArray['fraudRecommendation']['code']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_400_when_business_transaction_operation_is_unknown(): void
    {
        $memberId = 'd6fe61d0-4b75-413e-9ff0-fdb37b546824';
        $itemId   = '6eb4d350-3ecb-4a69-8c1f-4ef4e7307f4c';

        $payload                                 = $this->initPayload($memberId, $itemId);
        $payload["businessTransactionOperation"] = 'unknownOperation';

        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_400_when_business_transaction_operation_is_invalid_for_rebill_update(): void
    {
        $memberId = '73bb1504-9aa6-44df-a107-d9c6a2597b89';
        $itemId   = 'fa13507b-63a5-4dec-b412-b7e78b93eb77';

        $payload                                 = $this->initPayload($memberId, $itemId);
        $payload["businessTransactionOperation"] = 'singleCharge';

        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_400_when_legacy_mapping_is_in_the_request_but_not_product_id(): void
    {
        $memberId = '73bb1504-9aa6-44df-a107-d9c6a2597b89';
        $itemId   = 'fa13507b-63a5-4dec-b412-b7e78b93eb77';

        $payload                  = $this->initPayload($memberId, $itemId);
        $payload["legacyMapping"] = [
            'legacyMemberId' => 1234
        ];

        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_400_when_legacy_mapping_is_in_the_request_but_not_member_id(): void
    {
        $memberId = '7a68ca0a-3f6a-4d93-bec2-b9a4ba1addf3';
        $itemId   = '01e45f2b-1a2b-4f91-b32c-5b171ceb9835';

        $payload                  = $this->initPayload($memberId, $itemId);
        $payload["legacyMapping"] = [
            'legacyProductId' => 1234
        ];

        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_to_rebill_update_init_with_legacy_mapping(): void
    {
        $purchaseGatewayResponse = $this->makeAPurchase();
        $this->assertTrue($purchaseGatewayResponse['success'],'Failed Join Purchase.');
        $payload  = $this->initPayload($purchaseGatewayResponse['memberId'], $purchaseGatewayResponse['itemId']);

        $payload["legacyMapping"] = [
            'legacyProductId' => 1234,
            'legacyMemberId'  => 1234
        ];

        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseOk();
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function makeAPurchase(): array
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false, self::TESTING_SITE);
        $response->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');
        sleep(5); // Sleep between 1st Purchase and first Rebill Update or post-processing sometimes isn't done
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);
        sleep(10);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_to_rebill_update_init_with_add_remaining_days_flag_true(): void
    {
        $purchaseGatewayResponse = $this->makeAPurchase();
        $this->assertTrue($purchaseGatewayResponse['success'],'Failed Join Purchase.');
        $payload  = $this->initPayload($purchaseGatewayResponse['memberId'], $purchaseGatewayResponse['itemId'], true);
        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseOk();
    }

    /**
     * @return string
     */
    public function initUri(): string
    {
        return '/mgpg/api/v1/rebill-update/init';
    }

    /**
     * @param string $memberId
     * @param string $itemId
     * @param bool   $addRemainingDays
     *
     * @return array
     */
    public function initPayload(string $memberId, string $itemId, bool $addRemainingDays = false): array
    {
        return [
            "siteId"                       => self::TESTING_SITE,
            "memberId"                     => $memberId,
            'usingMemberProfile'           => true,
            "postbackUrl"                  => "https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall",
            "redirectUrl"                  => "http://localhost/postbackUrl",
            "itemId"                       => $itemId,
            "bundleId"                     => self::BUNDLE_ID,
            "addonId"                      => self::ADDON_ID,
            "legacyProductId"              => "15",
            "paymentType"                  => "cc",
            "paymentMethod"                => "visa",
            "clientIp"                     => "192.168.1.1",
            "clientCountryCode"            => "CA",
            "amount"                       => 187.6,
            "initialDays"                  => 33,
            "rebillDays"                   => 44,
            "rebillAmount"                 => 187.6,
            "addRemainingDays"             => $addRemainingDays,
            "currency"                     => "USD",
            "atlasCode"                    => "NDU1MDk1OjQ4OjE0Nw",
            "atlasData"                    => "atlas data example",
            "businessTransactionOperation" => "subscriptionUpgrade",
            "overrides"         => [
                "fraudService"        => [
                    "callInitVisitor" => [
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
                            "name"           => "rocketgate",
                            "type"           => 0,
                            "supports3DS"    => true,
                            "isLegacyBiller" => false,
                            "sendAllCharges" => false,
                            "createdAt"      => null,
                            "updatedAt"      => null
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string $memberId
     * @param string $itemId
     * @return array
     */
    public function initPayloadForcingFraud(string $memberId, string $itemId): array
    {
        return [
            "siteId"                       => self::TESTING_SITE,
            "memberId"                     => $memberId,
            "postbackUrl"                  => "https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall",
            "redirectUrl"                  => "http://localhost/postbackUrl",
            "itemId"                       => $itemId,
            "bundleId"                     => self::BUNDLE_ID,
            "addonId"                      => self::ADDON_ID,
            "legacyProductId"              => "15",
            "paymentType"                  => "cc",
            "paymentMethod"                => "visa",
            "clientIp"                     => "192.168.1.1",
            "clientCountryCode"            => "CA",
            "amount"                       => 187.6,
            "initialDays"                  => 33,
            "rebillDays"                   => 44,
            "rebillAmount"                 => 187.6,
            "currency"                     => "USD",
            "atlasCode"                    => "NDU1MDk1OjQ4OjE0Nw",
            "atlasData"                    => "atlas data example",
            "businessTransactionOperation" => "subscriptionUpgrade",
            "overrides"         => [
                "fraudService"=> [
                    "callInitCustomer"=> [
                        [
                            "severity" => "Block",
                            "code"     => 100,
                            "message"  => "Blacklist"
                        ]
                    ]
                ],
                "cachedConfigService" => [
                    "getAllBillerConfigs" => [
                        [
                            "name"           => "rocketgate",
                            "type"           => 0,
                            "supports3DS"    => true,
                            "isLegacyBiller" => false,
                            "sendAllCharges" => false,
                            "createdAt"      => null,
                            "updatedAt"      => null
                        ]
                    ]
                ]
            ]
        ];
    }
}
