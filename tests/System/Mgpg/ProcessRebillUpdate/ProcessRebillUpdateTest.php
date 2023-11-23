<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessRebillUpdate;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class ProcessRebillUpdateTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_on_rebill_update_process(): array
    {
        $token    = $this->makeRebillUpdateInit();
        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processRebillUpdatePayload(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertTrue(json_decode($response->response->getContent(), true)['success']);
        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_on_rebill_update_process
     * @param array $successResponse
     */
    public function it_should_return_invoice_on_success_response(array $successResponse): void
    {
        $this->assertArrayHasKey('invoice', $successResponse);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_fraud_on_rebill_update_process(): void
    {
        $token = $this->makeRebillUpdateInit();

        $this->override([
            "fraudService" => [
                "callProcessCustomer" => [
                    [
                        "severity" => "Block",
                        "code"     => 100,
                        "message"  => "Blacklist"
                    ]
                ]
            ]
        ]);

        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processRebillUpdatePayload(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_OK);
        $responseArray = json_decode($this->response->getContent(), true);
        $this->assertTrue($responseArray['fraudAdvice']['blacklist']);
        $this->assertEquals(FraudRecommendation::BLACKLIST, $responseArray['fraudRecommendation']['code']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function makeRebillUpdateInit(?float $amount = null): string
    {
        $purchaseGatewayResponse = $this->makeAPurchase();
        $payload = $this->initPayload(
            $purchaseGatewayResponse['memberId'],
            $purchaseGatewayResponse['itemId'],
            false,
            $amount
        );
        $response = $this->json('POST', $this->initUri(), $payload, $this->initPurchaseHeaders($this->businessGroupTestingXApiKey()));
        $response->assertResponseStatus(Response::HTTP_OK);

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function makeAPurchase(): array
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(
            false,
            self::TESTING_SITE,
            ['currency' => 'EUR']
        );

        $response->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');
        sleep(2);
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
    public function it_should_return_success_for_ach_payment_template_purchase(): void
    {
        $initPaymentTemplateWithToken = $this->makeACHRebillUpdateInit();

        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processPaymentTemplateRebillUpdatePayload($initPaymentTemplateWithToken['paymentTemplateInfo']),
            $this->processPurchaseHeaders($initPaymentTemplateWithToken['token'])
        );
        $response->assertResponseStatus(Response::HTTP_OK);

        $this->assertTrue(json_decode($response->response->getContent(), true)['success']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_for_ach_new_check_from_ach_purchase(): void
    {
        $initPaymentTemplateWithToken = $this->makeACHRebillUpdateInit();

        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processPurchasePayloadForChequePaymentType(),
            $this->processPurchaseHeaders($initPaymentTemplateWithToken['token'])
        );
        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertTrue(json_decode($response->response->getContent(), true)['success']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_for_ach_new_check_from_cc_purchase(): void
    {
        $initToken = $this->makeACHRebillUpdateInitWithPurchaseCC();

        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processPurchasePayloadForChequePaymentType(),
            $this->processPurchaseHeaders($initToken)
        );
        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertTrue(json_decode($response->response->getContent(), true)['success']);
    }

    /**
     * @throws \Exception
     */
    public function makeACHRebillUpdateInit(): array
    {
        $purchaseGatewayResponse = $this->makeACHPurchase();
        $header                  = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());
        $payload                 = $this->initACHRebillUpdatePayload(
            $purchaseGatewayResponse['memberId'],
            $purchaseGatewayResponse['itemId']
        );
        $response                = $this->json('POST', $this->initUri(), $payload, $header);
        $response->assertResponseStatus(Response::HTTP_OK);

        $response->seeHeader('X-Auth-Token');

        return [
            'token'               => (string) $this->response->headers->get('X-Auth-Token'),
            'paymentTemplateInfo' => json_decode($this->response->getContent(), true)['paymentTemplateInfo'][0]
        ];
    }

    /**
     * @throws \Exception
     */
    public function makeACHRebillUpdateInitWithPurchaseCC(): string
    {
        $purchaseGatewayResponse = $this->makeAPurchase();
        $header                  = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());
        $payload                 = $this->initACHRebillUpdatePayload(
            $purchaseGatewayResponse['memberId'],
            $purchaseGatewayResponse['itemId']
        );
        $response                = $this->json('POST', $this->initUri(), $payload, $header);
        $response->assertResponseStatus(Response::HTTP_OK);

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function makeACHPurchase(): array
    {
        $headers  = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());
        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayloadForChequePurchase(),
            $headers
        );
        $response->seeHeader('X-Auth-Token');
        $token = (string)$this->response->headers->get('X-Auth-Token');
        sleep(2);
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadForChequePaymentType(),
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
    public function it_should_return_success_on_rebill_update_process_with_add_remaining_days(): array
    {
        $token    = $this->makeRebillUpdateInit();
        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processRebillUpdatePayload(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertTrue(json_decode($response->response->getContent(), true)['success']);
        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_on_rebill_update_process_with_add_remaining_days
     * @param array $successResponse
     */
    public function it_should_return_invoice_on_success_response_with_add_remaining_days(array $successResponse): void
    {
        $this->assertArrayHasKey('invoice', $successResponse);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_render_gateway_next_action_on_declined_rebill_update_process(): void
    {
        $token    = $this->makeRebillUpdateInit(0.01);
        $response = $this->json(
            'POST',
            $this->processUri(),
            $this->processRebillUpdatePayload(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_OK);
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('renderGateway', $responseData['nextAction']['type']);
    }

    /**
     * @return string
     */
    public function initUri(): string
    {
        return '/mgpg/api/v1/rebill-update/init';
    }

    /**
     * @return string
     */
    public function processUri(): string
    {
        return '/mgpg/api/v1/rebill-update/process';
    }

    /**
     * @param string $memberId
     * @param string $itemId
     * @param bool   $addRemainingDays
     *
     * @return array
     */
    public function initPayload(
        string $memberId,
        string $itemId,
        bool $addRemainingDays = false,
        ?float $amount = null
    ): array
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
            "clientCountryCode"            => "US",
            "amount"                       => $amount ?? 187.6,
            "initialDays"                  => 33,
            "rebillDays"                   => 44,
            "rebillAmount"                 => 187.6,
            "addRemainingDays"             => $addRemainingDays,
            "currency"                     => "USD",
            "atlasCode"                    => "NDU1MDk1OjQ4OjE0Nw",
            "atlasData"                    => "atlas data example",
            "businessTransactionOperation" => "subscriptionUpgrade",
            "overrides"                    => [
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
     * @param string $siteId Site id
     * @return array
     * @throws \Exception
     */
    protected function processRebillUpdatePayload(string $siteId): array
    {
        $ccNumber = $this->faker->creditCardNumber('MasterCard');
        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $this->faker->email,
                'username'    => $this->faker->userName,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'type'                => 'cc',
                'method'              => 'visa',
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param array $paymentTemplateInfo
     * @return array
     */
    protected function processPaymentTemplateRebillUpdatePayload(array $paymentTemplateInfo): array
    {
        return [
            'siteId'  => self::TESTING_SITE,
            'payment' => [
                'type'                       => 'checks',
                'method'                     => 'checks',
                "paymentTemplateInformation" => [
                    "accountNumberLast4" => "2233",
                    "paymentTemplateId"  => $paymentTemplateInfo['templateId']
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function processPaymentRebillUpdateAchPayload(): array
    {
        return [
            'siteId'  => self::TESTING_SITE,
            'payment' => [
                'type'             => 'checks',
                'method'           => 'checks',
                "checkInformation" => [
                    "routingNumber"       => "999999999",
                    "accountNumber"       => "112233",
                    "savingAccount"       => false,
                    "socialSecurityLast4" => "5233",
                    "label"               => "label"
                ]
            ]
        ];
    }

    /**
     * @param string $memberId
     * @param string $itemId
     * @return array
     */
    public function initACHRebillUpdatePayload(string $memberId, string $itemId): array
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
            "paymentType"                  => "checks",
            "paymentMethod"                => "checks",
            "clientIp"                     => "192.168.1.1",
            "clientCountryCode"            => "US",
            "amount"                       => 187.6,
            "initialDays"                  => 33,
            "rebillDays"                   => 44,
            "rebillAmount"                 => 187.6,
            "currency"                     => "USD",
            "atlasCode"                    => "NDU1MDk1OjQ4OjE0Nw",
            "atlasData"                    => "atlas data example",
            "businessTransactionOperation" => "subscriptionUpgrade",
            "overrides"                    => [
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
     * @return array
     * @throws \Exception
     */
    public function initPurchasePayloadForChequePurchase(): array
    {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'bundleId' => self::BUNDLE_ID,
                'addonId'  => self::ADDON_ID,
            ]
        );

        return [
            'siteId'            => ProcessPurchaseBase::TESTING_SITE,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => 'USD',
            'clientIp'          => '10.10.109.185',
            'paymentType'       => 'checks',
            'paymentMethod'     => 'checks',
            'clientCountryCode' => 'US',
            'amount'            => 29.99,
            'initialDays'       => 5,
            'rebillDays'        => 30,
            'rebillAmount'      => 29.99,
            'atlasCode'         => 'NDU1MDk1OjQ4OjE0Nw',
            'atlasData'         => 'atlas data example',
            'isTrial'           => false,
            'redirectUrl'       => $this->faker->url,
            'tax'               => [
                'initialAmount'    => [
                    'beforeTaxes' => 28.56,
                    'taxes'       => 1.43,
                    'afterTaxes'  => 29.99
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => 28.56,
                    'taxes'       => 1.43,
                    'afterTaxes'  => 29.99
                ],
                'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                'taxName'          => 'VAT',
                'taxRate'          => 0.05,
                'taxType'          => 'VAT',
            ],
            'dws'               => [
                'maxMind' => [
                    'x-geo-city'        => 'Salvador',
                    'x-geo-postal-code' => 'H0H0H0H0'
                ]
            ]
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadForChequePaymentType(): array
    {
        return [
            'siteId'  => self::TESTING_SITE,
            'member'  => [
                'email'       => $this->faker->email,
                'username'    => $this->faker->userName,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'US',
                'zipCode'     => '89141',
                'address1'    => '123 Main St',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Las Vegas',
                'state'       => 'NV',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                "checkInformation" => [
                    "routingNumber"       => $this->faker->numberBetween(10000000,999999999),
                    "accountNumber"       => "112233",
                    "savingAccount"       => false,
                    "socialSecurityLast4" => "5233",
                    "label"               => "label"
                ]
            ]
        ];
    }
}
