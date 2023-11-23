<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class ProcessPurchaseWithGiftcardsPaymentTypeTest extends ProcessPurchaseBase
{
    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function it_should_fail_when_payment_method_and_type_in_process_are_wrong(
    ): void
    {
        $token = $this->initGiftcards();

        $giftcardsPaymentInformationWithoutPaygarden = [
            'method' => 'unknown',
            'type'   => 'unknown'
        ];

        $requestPayload            = $this->processPurchasePayloadWithNoSelectedCrossSale(ProcessPurchaseBase::TESTING_SITE);
        $requestPayload['payment'] = $giftcardsPaymentInformationWithoutPaygarden;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $requestPayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function it_should_return_success_when_giftcards_payment_is_set_correctly(): array
    {
        $token = $this->initGiftcards();

        $giftcardsPaymentInformationWithoutPaygarden = [
            'currency' => 'USD',
            'method'   => PaymentMethod::GIFTCARDS,
            'type'     => PaymentType::GIFTCARDS
            ];

        $requestPayload              = $this->processPurchasePayloadWithNoSelectedCrossSale(ProcessPurchaseBase::TESTING_SITE);
        $requestPayload['payment']   = $giftcardsPaymentInformationWithoutPaygarden;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $requestPayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_success_when_giftcards_payment_is_set_correctly
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_with_true_value_for_paygarden_purchase(array $response
    ): void {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends it_should_return_success_when_giftcards_payment_is_set_correctly
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_redirect_type_for_paygarden_purchase(array $response): void {
        $this->assertEquals('redirectToUrl', $response["nextAction"]['type']);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function initGiftcards()
    {
        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchaseGiftcardsPayload(
                ProcessPurchaseBase::TESTING_SITE,
                [
                    'paymentMethod' => PaymentMethod::GIFTCARDS,
                    'paymentType'   => PaymentType::GIFTCARDS,
                    "otherData"     => [
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
                                                    'siteId' => self::TESTING_SITE,
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
                ]
            ),
            $this->initPurchaseHeaders($this->businessGroupTestingXApiKey())
        );

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }
}