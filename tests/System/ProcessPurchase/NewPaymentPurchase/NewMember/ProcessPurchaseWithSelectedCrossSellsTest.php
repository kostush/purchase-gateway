<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithSelectedCrossSellsTest extends ProcessPurchaseBase
{

    protected $selectedCrossSells = [
        [
            'bundleId' => '4475820e-2956-11e9-b210-d663bd873d93',
            'addonId'  => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
            'siteId'   => self::TESTING_SITE_NO_FRAUD
        ],
    ];

    protected $invalidSelectedCrossSells = [
        [
            'priceId'  => '5531d782-2956-11e9-b210-d663bd873d93',
            'bundleId' => '4475820e-2956-11e9-b210-d663bd873d93',
            'addonId'  => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
        ],
        [
            'priceId'  => '5531d782-2956-11e9-b210-d663bd873d93',
            'bundleId' => '4475820e-2956-11e9-b210-d663bd873d93',
            'addonId'  => '4e1b0d7e-2956-11e9-b210-d663bd873d92',
        ],
    ];

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
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_for_purchase_should_contain_x_auth_token_for_invalid_selected_cross_sells(): string
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
    public function process_purchase_should_return_success($token): array
    {
        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPurchasePayload['selectedCrossSells'] = $this->selectedCrossSells;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($token)
        );

        $responseJson = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('fraudRecommendation', $responseJson, self::RETURNING_NOT_EXPECTED_FRAUD);
        $response->assertResponseStatus(Response::HTTP_OK);
        $this->assertTrue($responseJson['success']);
        return $responseJson;
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_should_contain_x_auth_token_for_invalid_selected_cross_sells
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_should_return_bad_request_with_invalid_selected_cross_sells($token): void
    {
        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPurchasePayload['selectedCrossSells'] = $this->invalidSelectedCrossSells;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($token)
        );
        $responseJson = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('fraudRecommendation', $responseJson, self::RETURNING_NOT_EXPECTED_FRAUD);

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_crossSells_key(array $response): void
    {
        $this->assertArrayHasKey('crossSells', $response);
    }


    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_crossSells_with_success_key(array $response): void
    {
        $this->assertArrayHasKey('success', $response['crossSells'][0]);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_crossSells_with_bundleId_key(array $response): void
    {
        $this->assertArrayHasKey('bundleId', $response['crossSells'][0]);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_crossSells_with_addonId_key(array $response): void
    {
        $this->assertArrayHasKey('addonId', $response['crossSells'][0]);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_crossSells_with_subscriptionId_key(array $response): void
    {
        $this->assertArrayHasKey('subscriptionId', $response['crossSells'][0]);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_crossSells_with_transactionId_key(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response['crossSells'][0]);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_contain_billerName_key_only_for_main_purchase(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
        $this->assertArrayNotHasKey('billerName', $response['crossSells'][0]);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_response_should_contain_crossSells_with_bundleId_value_equal_to_bundleId_from_initial_purchase_request(
        array $response
    ): void {
        $this->assertEquals($this->initPurchasePayload()['bundleId'], $response['crossSells'][0]['bundleId']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_response_should_contain_crossSells_with_addonId_value_equal_to_addonId_from_initial_purchase_request(
        array $response
    ): void {
        $this->assertEquals($this->initPurchasePayload()['addonId'], $response['crossSells'][0]['addonId']);
    }

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_for_purchase_should_contain_x_auth_token_with_netbilling_biller(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSaleForcedBillerAsNetbilling(false);

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_for_purchase_should_contain_x_auth_token_with_netbilling_biller
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success_with_netbilling_biller($token): array
    {
        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedCrossSaleForNetbillingBiller();

        $processPurchasePayload['selectedCrossSells'] = $this->selectedCrossSells;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }
}
