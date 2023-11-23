<?php
namespace Tests\System\Mgpg\InitPurchase;

/**
 * @group InitPurchase
 */
class FormatTest extends InitPurchase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_should_return_success(): array
    {
        return parent::purchase_initiating_should_return_success();
    }

    /**
     * @test
     * @depends purchase_initiating_should_return_success
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_has_session_id_key(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends purchase_initiating_should_return_success
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_has_payment_processor_type_key(array $response): void
    {
        $this->assertArrayHasKey('paymentProcessorType', $response);
    }

    /**
     * @test
     * @depends purchase_initiating_should_return_success
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_has_fraud_advice_key(array $response): void
    {
        $this->assertArrayHasKey('fraudAdvice', $response);
    }
}
