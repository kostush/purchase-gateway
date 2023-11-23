<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseProductWithoutCrossSellsTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_for_purchase_should_contain_x_auth_token(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false);
        $response->seeHeader('X-Auth-Token');
        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_when_product_has_no_cross_sale($token): array
    {
        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_when_product_has_no_cross_sale
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_not_contain_crossSells_key(array $response): void
    {
        $this->assertArrayNotHasKey('crossSells', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success_when_product_has_no_cross_sale
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_billerName_key(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
    }

    /**
     * @param string $siteId Site id
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithNoSelectedCrossSale(string $siteId = self::TESTING_SITE): array
    {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');
        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@test.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => 'Mister',
                'lastName'    => 'Axe',
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }
}
