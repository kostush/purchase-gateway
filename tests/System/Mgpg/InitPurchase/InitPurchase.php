<?php

namespace Tests\System\Mgpg\InitPurchase;

use GuzzleHttp\Client;
use Illuminate\Http\Response;
use ProbillerMGPG\ClientApi;
use Tests\SystemTestCase;

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
    protected const PORNHUB_PREMIUM_SITE_ID = '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2';
    protected const COUNTRY_ROCKETGATE      = 'RO';

    protected const TESTING_SITE            = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';

    protected const IP_WITH_CAPTCHA = '5.135.109.185';
    protected const BUNDLE_ID       = '5fd44440-2956-11e9-b210-d663bd873d93';
    protected const ADDON_ID        = '670af402-2956-11e9-b210-d663bd873d93';

    public const NO_FRAUD = [
        'captcha'   => false,
        'blacklist' => false
    ];

    /**
     * @var ClientApi
     */
    protected $clientApi;

    protected $payload;

    protected $headers = [];

    protected function override(array $overrides = [])
    {
        $this->clientApi->setOverrides($overrides);

        // Sets MGPG ClientApi that is normally injected on post/get calls through Lumen for this one
        $this->app->instance(ClientApi::class, $this->clientApi);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $clientId = config('clientapis.mgpg.aadAuth.clientId');
        $secret   = config('clientapis.mgpg.aadAuth.clientSecret');
        $env      = config('clientapis.mgpg.aadAuth.env');

        $this->clientApi = new ClientApi(new Client(['base_uri' => $env]), $clientId, $secret);

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
            'currency'          => 'USD',
            'clientIp'          => '0.0.0.4',
            'paymentType'       => 'cc',
            'clientCountryCode' => self::COUNTRY_ROCKETGATE,
            'amount'            => $faker->numberBetween(),
            'initialDays'       => $faker->numberBetween(1, 30),
            'rebillDays'        => $faker->numberBetween(1, 30),
            'rebillAmount'      => $faker->randomFloat(1, 100),
            'paymentMethod'     => null,
            'trafficSource'     => 'ALL',
            'usingMemberProfile'=> true,
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
        return 'mgpg/api/v1/purchase/init';
    }

    /**
     * @return array
     */
    protected function header(): array
    {
        return array_merge(
            [
                'Content-Type' => 'application/json',
                'x-api-key'    => $this->businessGroupTestingXApiKey()
            ],
            $this->headers
        );
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_should_return_success(): array
    {
        $response = $this->json('POST', $this->validRequestUri(), $this->payload, $this->header());
        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
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
        $this->payload['paymentType'] = 'someType';
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
        $this->markTestIncomplete(
            'This test makes no sense for MGPG because bundle/addon concept is not applicable.' // TODO
        );
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
        $this->markTestIncomplete(
            'This test makes no sense for MGPG because bundle/addon concept is not applicable.' // TODO
        );
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
