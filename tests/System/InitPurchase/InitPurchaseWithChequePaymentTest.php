<?php

namespace Tests\System\InitPurchase;

use Illuminate\Http\Response;
use Tests\SystemTestCase;

class InitPurchaseWithChequePaymentTest extends SystemTestCase
{
    protected const PORNHUB_PREMIUM_SITE_ID = '299b14d0-cf3d-11e9-8c91-0cc47a283dd2';
    protected const REALITY_KINGS_SITE_ID   = '8e34c94e-135f-4acb-9141-58b3a6e56c74';
    protected const COUNTRY_ROCKETGATE      = 'RO';

    protected const IP_WITH_CAPTCHA = '5.135.109.185';
    protected const BUNDLE_ID       = '5fd44440-2956-11e9-b210-d663bd873d93';
    protected const ADDON_ID        = '670af402-2956-11e9-b210-d663bd873d93';

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
            'siteId'            => self::REALITY_KINGS_SITE_ID,
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
            'x-api-key'    => $this->paysitesXApiKey()
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
}
