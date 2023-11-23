<?php

namespace Tests\System\Mgpg\ProcessPurchase\ExistingPaymentPurchase;

use Illuminate\Http\Response;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithoutSubscriptionIdTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_without_subscription_id_should_contain_x_auth_token(): string
    {
        $response = $this->initExistingMemberWithoutSubscriptionId();
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_without_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_should_return_error_when_username_or_password_not_in_process_payload($token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processExistingMemberPurchasePayload(),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }
}
