<?php

namespace Tests\System\InitPurchase;

use Tests\SystemTestCase;
use Illuminate\Http\Response;

/**
 * @group InitPurchase
 */
abstract class InitPurchase extends SystemTestCase
{
    /**
     * When using the PORNHUB_PREMIUM_SITE_ID=299b14d0-cf3d-11e9-8c91-0cc47a283dd2
     * along with COUNTRY_ROCKETGATE=RO, the cascade service will always provide
     * Rocketgate as the first biller in cascade. This is useful for the system tests
     * that were developed in the early stages of the project, when only Rocketgate
     * was supported.
     */
    protected const TESTING_SITE            = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';
    protected const TESTING_SITE_NO_FRAUD   = '0ee56671-1eaf-414c-9b0e-ee7f1a8ded96';
    protected const COUNTRY_ROCKETGATE      = 'RO';

    protected const IP_WITH_CAPTCHA = '5.135.109.185';
    protected const BUNDLE_ID       = '5fd44440-2956-11e9-b210-d663bd873d93';
    protected const ADDON_ID        = '670af402-2956-11e9-b210-d663bd873d93';

    public const NO_FRAUD = [
        'captcha'   => false,
        'blacklist' => false
    ];

    protected $payload;

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

        $this->payload = [
            'siteId'            => self::TESTING_SITE,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => 'JPY',
            'clientIp'          => '0.0.0.4',
            'paymentType'       => 'cc',
            'clientCountryCode' => self::COUNTRY_ROCKETGATE,
            'amount'            => $faker->numberBetween(),
            'initialDays'       => $faker->numberBetween(1, 30),
            'rebillDays'        => $faker->numberBetween(1, 30),
            'rebillAmount'      => $faker->randomFloat(1, 100),
            'paymentMethod'     => null,
            'trafficSource'     => 'ALL'
        ];
    }

    /**
     * @test
     * @depends purchase_initiating_should_return_success
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_with_excessive_initial_days_should_return_failure(): array
    {
        $this->payload['initialDays'] = 10001;

        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends purchase_initiating_should_return_success
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_with_excessive_rebill_days_should_return_failure(): array
    {
        $this->payload['rebillDays'] = 10001;

        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($this->response->getContent(), true);
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
     */
    public function purchase_initiating_should_return_success(): array
    {
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @dataProvider getDifferentPaymentMethod
     * @param string|null $paymentMethod
     */
    public function purchase_initiating_with_different_paymentMethod_should_return_success($paymentMethod)
    {
        $this->payload['paymentMethod'] = $paymentMethod;

        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());

        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @return \string[][]
     */
    public function getDifferentPaymentMethod() {
        return [
            'default' => [
                'paymentMethod' => null
            ],
            'mir'  => [
                'paymentMethod' => 'mir'
            ],
            'ccunionpay'  => [
                'paymentMethod' => 'ccunionpay'
            ],
            'visa'  => [
                'paymentMethod' => 'visa'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getUnsupportedPaymentMethod
     * @param string|null $paymentMethod
     */
    public function purchase_initiating_with_unsupported_paymentMethod_should_return_failure($paymentMethod)
    {
        $this->payload['paymentMethod'] = $paymentMethod;

        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return \string[][]
     */
    public function getUnsupportedPaymentMethod() {
        return [
            'case1' => [
                'paymentMethod' => 'bismark'
            ],
            'case2'  => [
                'paymentMethod' => 'null'
            ],
        ];
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_free_sale_should_have_amount_zero(): array
    {
        $this->payload['amount'] = 0;

        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @return array
     */
    public function purchase_initiating_should_return_bad_request_when_payment_type_is_unsupported(): array
    {
        $this->payload['paymentType'] = 'check';
        $response                     = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @return array
     */
    public function purchase_initiating_should_return_not_found_when_bundle_id_does_not_exist(): array
    {
        $this->payload['bundleId'] = $this->faker->uuid;
        $response                  = $this->json(
            'POST',
            $this->validRequestUri(),
            $this->payload,
            $this->header()
        );
        $response->assertResponseStatus(Response::HTTP_NOT_FOUND);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @return array
     */
    public function purchase_initiating_should_return_bad_request_when_addon_id_does_not_belong_to_bundle(): array
    {
        $this->payload['addonId'] = $this->faker->uuid;
        $response                 = $this->json(
            'POST',
            $this->validRequestUri(),
            $this->payload,
            $this->header()
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($this->response->getContent(), true);
    }
}
