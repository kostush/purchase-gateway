<?php
namespace Tests\System\Mgpg\InitPurchase;

use Ramsey\Uuid\Uuid;

/**
 * @group InitPurchase
 */
class InitPurchaseWithCorrelationTest extends InitPurchase
{
    protected const CORRELATION_ID_HEADER_KEY = 'X-CORRELATION-ID';
    protected const VALID_CORRELATION_ID      = '61236e16-2802-370c-b584-ba817ca6ba1a';
    protected const INVALID_CORRELATION_ID    = 'someinvalidvalue';

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_with_valid_correlation_id_should_return_success(): array
    {
        $this->headers = [
            self::CORRELATION_ID_HEADER_KEY => self::VALID_CORRELATION_ID,
        ];

        return parent::purchase_initiating_should_return_success();
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_with_invalid_correlation_id_should_return_success(): array
    {
        $this->headers = [
            self::CORRELATION_ID_HEADER_KEY => self::INVALID_CORRELATION_ID,
        ];

        return parent::purchase_initiating_should_return_success();
    }

    /**
     * @test
     * @depends purchase_initiating_with_valid_correlation_id_should_return_success
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_have_same_correlation_id_as_requested(array $response): void
    {
        $this->assertEquals(self::VALID_CORRELATION_ID, $response['correlationId']);
    }

    /**
     * @test
     * @depends purchase_initiating_with_invalid_correlation_id_should_return_success
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_should_have_valid_correlation_id_when_invalid_correlation_was_requested(array $response): void
    {
        $this->assertNotEquals(self::INVALID_CORRELATION_ID, $response['correlationId']);
        $this->assertTrue(Uuid::isValid($response['correlationId']));
    }
}
