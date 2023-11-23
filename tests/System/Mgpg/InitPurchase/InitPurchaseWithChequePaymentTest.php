<?php

namespace Tests\System\Mgpg\InitPurchase;

use http\Exception\InvalidArgumentException;
use Illuminate\Http\Response;
use Tests\SystemTestCase;

class InitPurchaseWithChequePaymentTest extends SystemTestCase
{
    protected const COUNTRY_ROCKETGATE      = 'RO';

    protected const IP_WITH_CAPTCHA = '5.135.109.185';
    protected const BUNDLE_ID       = '5fd44440-2956-11e9-b210-d663bd873d93';
    protected const ADDON_ID        = '670af402-2956-11e9-b210-d663bd873d93';

    protected const TESTING_SITE           = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';

    public const NO_FRAUD = [
        'captcha'   => false,
        'blacklist' => false
    ];

    protected $payload;

    protected $initPayload;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $faker = \Faker\Factory::create();

        $bundle = $this->createAndAddBundleToRepository(
            [
                'addonId'  => '5fd44440-2956-11e9-b210-d663bd873d93',
                'bundleId' => '670af402-2956-11e9-b210-d663bd873d93',
            ]
        );

        $this->initPayload = [
            'siteId'            => self::TESTING_SITE,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => 'USD',
            'clientIp'          => '0.0.0.4',
            'paymentType'       => 'checks',
            'paymentMethod'     => 'checks',
            'clientCountryCode' => self::COUNTRY_ROCKETGATE,
            'amount'            => $faker->numberBetween(),
            'initialDays'       => $faker->numberBetween(1, 30),
            'rebillDays'        => $faker->numberBetween(1, 30),
            'rebillAmount'      => $faker->randomFloat(1, 100),
            'trafficSource'     => 'ALL'
        ];
    }

    /**
     * @return string
     */
    protected function validRequestUri(): string
    {
        return '/api/v1/purchase/init';
    }

    /**
     * @return array
     */
    protected function header(): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key'    => $this->businessGroupTestingXApiKey()
        ];
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initialization_should_return_success_when_we_use_checks_as_payment_type(): array
    {
        $response = $this->json('POST', $this->validRequestUri(), $this->initPayload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);
        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends purchase_initialization_should_return_success_when_we_use_checks_as_payment_type
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_has_session_id_key_for_checks_payment_type_payment(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends purchase_initialization_should_return_success_when_we_use_checks_as_payment_type
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_has_payment_processor_type_key_for_checks_payment_type_payment(array $response): void
    {
        $this->assertArrayHasKey('paymentProcessorType', $response);
    }

    /**
     * @test
     * @depends purchase_initialization_should_return_success_when_we_use_checks_as_payment_type
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_has_fraud_advice_key_for_checks_payment_type_payment(array $response): void
    {
        $this->assertArrayHasKey('fraudAdvice', $response);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_error_validation_when_ach_init_does_not_have_max_mind_city_and_postal_code(): void
    {
        $headers  = $this->header();
        $response = $this->json(
            'POST',
            $this->mgpgInitUri(),
            $this->initPurchasePayloadForChequePurchaseWithoutGeolocationInformation(),
            $headers
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
        $this->assertEquals(
            json_decode($response->response->getContent(), true)['error'],
        'The given data was invalid. The dws.max mind.x-geo-city field is required when payment type is checks. |'.
              ' The dws.max mind.x-geo-postal-code field is required when payment type is checks.');
    }

    /**
     * @return string
     */
    public function mgpgInitUri(): string
    {
        return '/mgpg/api/v1/purchase/init';
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function initPurchasePayloadForChequePurchaseWithoutGeolocationInformation(): array
    {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'bundleId' => self::BUNDLE_ID,
                'addonId'  => self::ADDON_ID,
            ]
        );

        return [
            'siteId'            => self::TESTING_SITE,
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
            ]
        ];
    }
}
