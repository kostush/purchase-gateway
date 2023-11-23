<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class ProcessPurchaseWithCryptoTest extends ProcessPurchaseBase
{
    const CRYPTOCURRENCY_COINPAYMENT_LITECOIN = 'LTCT';

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function it_should_fail_when_payment_method_and_type_are_cryptocurrency_but_no_crypto_currency_is_sent(
    ): void
    {
        $token = $this->initCrypto();

        $cryptoPaymentInformationWithoutCryptoCurrency = [
            'method' => PaymentMethod::CRYPTOCURRENCY,
            'type'   => PaymentType::CRYPTOCURRENCY
        ];

        $requestPayload            = $this->processPurchasePayloadWithNoSelectedCrossSale(ProcessPurchaseBase::TESTING_SITE);
        $requestPayload['payment'] = $cryptoPaymentInformationWithoutCryptoCurrency;

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
    public function it_should_return_success_when_crypto_payment_is_set_correctly(): array
    {
        $token = $this->initCrypto();

        $cryptoPaymentInformationWithoutCryptoCurrency = [
            'method'         => PaymentMethod::CRYPTOCURRENCY,
            'type'           => PaymentType::CRYPTOCURRENCY,
            'cryptoCurrency' => self::CRYPTOCURRENCY_COINPAYMENT_LITECOIN
        ];

        $requestPayload            = $this->processPurchasePayloadWithNoSelectedCrossSale(ProcessPurchaseBase::TESTING_SITE);
        $requestPayload['payment'] = $cryptoPaymentInformationWithoutCryptoCurrency;

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
     * @depends it_should_return_success_when_crypto_payment_is_set_correctly
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_with_true_value_for_crypto_purchase(array $response
    ): void {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends it_should_return_success_when_crypto_payment_is_set_correctly
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_redirect_type_for_crypto_purchase(array $response): void {
        $this->assertEquals('redirectToUrl', $response["nextAction"]['type']);
    }


    /**
     * @return string
     * @throws \Exception
     */
    protected function initCrypto()
    {
        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            array (
                'overrides' =>
                    array (
                        'overrides' =>
                            array (
                                'cascade' =>
                                    array (
                                        'callCascades' =>
                                            array (
                                                'billers' =>
                                                    array (
                                                        0 => 'coinpayments',
                                                    ),
                                            ),
                                    ),
                                'fraudService' =>
                                    array (
                                        'callInitVisitor' =>
                                            array (
                                                0 =>
                                                    array (
                                                        'severity' => 'Allow',
                                                        'code' => 1000,
                                                        'message' => 'Allow',
                                                    ),
                                            ),
                                    ),
                                'cachedConfigService' =>
                                    array (
                                        'getAllBillerConfigs' =>
                                            array (
                                                0 =>
                                                    array (
                                                        'name' => 'rocketgate',
                                                        'type' => 0,
                                                        'supports3DS' => true,
                                                        'isLegacyBiller' => false,
                                                        'sendAllCharges' => false,
                                                        'createdAt' => NULL,
                                                        'updatedAt' => NULL,
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
                'siteId' => 'a2d4f06f-afc8-41c9-9910-0302bd2d9531',
                'bundleId' => '4475820e-2956-11e9-b210-d663bd873d93',
                'addonId' => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
                'currency' => 'EUR',
                'clientIp' => '10.10.109.185',
                'paymentType' => 'cryptocurrency',
                'paymentMethod' => 'cryptocurrency',
                'clientCountryCode' => 'US',
                'amount' => 29.99,
                'initialDays' => 5,
                'atlasCode' => 'NDU1MDk1OjQ4OjE0Nw',
                'atlasData' => 'atlas data example',
                'isTrial' => false,
                'postbackUrl' => 'https://www.kub.info/cum-blanditiis-illo-amet-asperiores',
                'redirectUrl' => 'http://www.bayer.com/sed-dolorem-veniam-earum-velit-ea',
                'tax' =>
                    array (
                        'initialAmount' =>
                            array (
                                'beforeTaxes' => 28.56,
                                'taxes' => 1.43,
                                'afterTaxes' => 29.99,
                            ),
                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                        'taxName' => 'VAT',
                        'taxRate' => 0.05,
                        'taxType' => 'VAT',
                    ),
                'crossSellOptions' =>
                    array (
                        0 =>
                            array (
                                'bundleId' => '4475820e-2956-11e9-b210-d663bd873d93',
                                'addonId' => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
                                'siteId' => 'a2d4f06f-afc8-41c9-9910-0302bd2d9531',
                                'initialDays' => 3,
                                'amount' => 1,
                                'isTrial' => false,
                                'tax' =>
                                    array (
                                        'initialAmount' =>
                                            array (
                                                'beforeTaxes' => 0.95,
                                                'taxes' => 0.05,
                                                'afterTaxes' => 1,
                                            ),
                                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                                        'taxName' => 'HST',
                                        'taxRate' => 0.05,
                                        'taxType' => 'sales',
                                    ),
                            ),
                    ),
            )
            ,
            $this->initPurchaseHeaders($this->businessGroupTestingXApiKey())
        );

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }
}