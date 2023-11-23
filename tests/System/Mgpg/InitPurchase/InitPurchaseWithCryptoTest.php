<?php
declare(strict_types=1);

namespace Mgpg\InitPurchase;

use Illuminate\Http\Response;
use Tests\SystemTestCase;

class InitPurchaseWithCryptoTest extends SystemTestCase
{
    const TESTING_SITE_ID     = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_crypto_settings_on_response(): void
    {
        $response = $this->json('POST', $this->validRequestUri(), $this->cryptoInitPayload(), $this->createInitHeader());
        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertArrayHasKey('cryptoSettings', json_decode($this->response->getContent(), true));
    }

    /**
     * @return string
     */
    protected function validRequestUri(): string
    {
        return '/mgpg/api/v1/purchase/init';
    }

    private function createInitHeader(): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key'    => $this->businessGroupTestingXApiKey()
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function cryptoInitPayload(): array
    {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'addonId'  => '5fd44440-2956-11e9-b210-d663bd873d93',
                'bundleId' => '670af402-2956-11e9-b210-d663bd873d93',
            ]
        );

        return [
            'siteId'            => self::TESTING_SITE_ID,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            "redirectUrl"       => "https://client-complete-return-url",
            "postbackUrl"       => "https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall",
            'currency'          => 'EUR',
            'clientIp'          => '0.0.0.4',
            'paymentType'       => 'cryptocurrency',
            "paymentMethod"     => "cryptocurrency",
            'clientCountryCode' => 'US',
            'amount'            => $this->faker->numberBetween(),
            'initialDays'       => 1,
            'trafficSource'     => 'ALL'
        ];
    }
}
