<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithCorrelation extends ProcessPurchaseBase
{
    protected const CORRELATION_ID_HEADER_KEY = 'X-CORRELATION-ID';
    protected const VALID_CORRELATION_ID      = '61236e16-2802-370c-b584-ba817ca6ba1a';

    /**
     * @param string|null $xApiKey X Api Key
     *
     * @return array
     */
    protected function initPurchaseHeaders(?string $xApiKey = null): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key'    => $xApiKey ?? $this->paysitesXApiKey(),
            self::CORRELATION_ID_HEADER_KEY => self::VALID_CORRELATION_ID,
        ];
    }

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false, self::TESTING_SITE);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success($token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(self::TESTING_SITE),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response
     *
     * @return void
     */
    public function process_purchase_response_should_have_same_correlation_id_as_requested(array $response): void
    {
        $this->assertEquals(self::VALID_CORRELATION_ID, $response['correlationId']);
    }
}
