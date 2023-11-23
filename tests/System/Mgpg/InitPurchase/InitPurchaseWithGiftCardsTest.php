<?php

namespace Tests\System\Mgpg\InitPurchase;

use Illuminate\Http\Response;
use Tests\SystemTestCase;

class InitPurchaseWithGiftCardsTest extends SystemTestCase
{
    const TESTING_SITE_ID     = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_success_settings_on_response(): void
    {
        $response = $this->json('POST', $this->validRequestUri(), $this->giftcardsInitPayload(), $this->createInitHeader());
        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @return string
     */
    protected function validRequestUri(): string
    {
        return '/mgpg/api/v1/purchase/init';
    }

    /**
     *
     */
    private function createInitHeader($xApiKey = null): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key'    => $xApiKey ?? $this->businessGroupTestingXApiKey()
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function giftcardsInitPayload(): array
    {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'addonId'  => '5fd44440-2956-11e9-b210-d663bd873d93',
                'bundleId' => '670af402-2956-11e9-b210-d663bd873d93',
            ]
        );

        $finalPrice = $this->faker->numberBetween(2);
        return [
            'siteId'            => self::TESTING_SITE_ID,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            "redirectUrl"       => "https://client-complete-return-url",
            "postbackUrl"       => "https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall",
            'currency'          => 'USD', // If changing currency, make sure to reflecte it in the override
            'clientIp'          => '0.0.0.4',
            'paymentType'       => 'giftcards',
            "paymentMethod"     => "giftcards",
            'clientCountryCode' => 'US',
            'amount'            => $finalPrice,
            'initialDays'       => 1,
            'trafficSource'     => 'ALL',
            'tax'               =>
                [
                    'custom' => 'yasd7 a7s8dyas78d yas7d8a7 syd8asydhasuidhasud ayshduyi sa',
                    'initialAmount' =>
                        [
                            'beforeTaxes' => $finalPrice,
                            'taxes' => 0,
                            'afterTaxes' => $finalPrice,
                        ],
                    'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                    'taxName' => 'VAT',
                    'taxRate' => 0.05,
                    'productClassification' => 'classification',
                    'taxType' => 'type1',
                ],
            "legacyMapping"     =>
                [
                    "data" => [
                        "legacyProductId" => 5903
                    ]
                ],
            "otherData"         =>
                [
                    "paygarden" => [
                        "data" => [
                            "credit" => 31,
                            "sku"=> "AAAA"
                        ]
                    ]
                ],
            'overrides' =>
                [
                    'fraudService' =>
                        [
                            'callInitVisitor' =>
                                [
                                    0 =>
                                        [
                                            'severity' => 'Allow',
                                            'code' => 1000,
                                            'message' => 'Allow',
                                        ],
                                ],
                        ],
                    'cascade' =>
                        [
                            'callCascades' =>
                                [
                                    'billers' =>
                                        [
                                            0 => 'paygarden',
                                        ],
                                ],
                        ],
                    'probillerConfigService' =>
                        [
                            'getAllBillerConfigs' =>
                                [
                                    0 =>
                                        [
                                            'name' => 'paygarden',
                                            'type' => 0,
                                            'supports3DS' => false,
                                            'isLegacyBiller' => false,
                                            'sendAllCharges' => true,
                                            'postbackTimeoutHours' => 0,
                                            'hasPostbacks' => true,
                                            'supportsAddCard' => false,
                                            'bypassPrimaryCharge' => true,
                                        ],
                                ],
                            'getAllBillerMappingConfigs' =>
                                [
                                    0 =>
                                        [
                                            'siteId' => self::TESTING_SITE_ID,
                                            'active' => true,
                                            'availableCurrencies' =>
                                                [
                                                    0 => 'USD',
                                                ],
                                            'biller' =>
                                                [
                                                    'billerFields' =>
                                                        [
                                                            'biller' =>
                                                                [
                                                                    'oneofKind' => 'paygarden',
                                                                    'paygarden' =>
                                                                        [
                                                                            'sku' => 'bigstr',
                                                                            'apiKey' => '1992b152-8da9-4ddf-8080-43184df84c01',
                                                                            'partnerDisplayName' => 'mg2',
                                                                        ],
                                                                ],
                                                        ],
                                                    'name' => 'paygarden',
                                                    'supports3DS1' => false,
                                                    'supports3DS2' => false,
                                                ],
                                            'billerMappingId' => '6e2537e5-9a92-4d85-b601-7196368c7a24',
                                            'businessGroupId' => NULL,
                                        ],
                                ],
                        ],
                ]
        ];
    }
}