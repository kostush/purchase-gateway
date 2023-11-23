<?php
declare(strict_types=1);

namespace Tests\System;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class LookupControllerTest extends ProcessPurchaseBase
{
    /**
     * @var string
     */
    private $lookupUrl;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->lookupUrl = '/api/v1/threed/lookup';
    }
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token(): string
    {
        $this->markTestIncomplete();
        $response = $this->initPurchaseProcessWithOneCrossSale(false, ProcessPurchaseBase::REALITY_KINGS_SITE_ID);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function threed_lookup_should_return_success($token): array
    {
        $this->markTestIncomplete();
        $response = $this->json(
            'POST',
            $this->lookupUrl,
            $this->lookupPayload(ProcessPurchaseBase::REALITY_KINGS_SITE_ID),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @param string $siteId Site id
     * @return array
     */
    protected function lookupPayload(
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ): array {
        return [
            'siteId'  => $siteId,
            'deviceFingerprintingId' => '4c3d9766-a8a4-4577-999c-a942905347b6',
            'payment' => [
                'ccNumber'            => $this->faker->creditCardNumber('Visa'),
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }
}
