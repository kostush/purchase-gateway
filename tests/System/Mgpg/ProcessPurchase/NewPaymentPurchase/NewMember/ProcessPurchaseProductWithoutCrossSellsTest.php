<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

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
        $response = $this->initPurchaseProcessWithOneCrossSale();
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
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
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
    public function adapter_process_purchase_response_should_not_contain_billerName_key(array $response): void
    {
        $this->assertArrayNotHasKey('billerName', $response);
    }
}
