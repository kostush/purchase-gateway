<?php

namespace Tests\System\Mgpg\InitPurchase;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;

/**
 * @group InitPurchase
 */
class NoCrossSellNoFraudTest extends InitPurchase
{
    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_should_return_success(): array
    {
        return parent::purchase_initiating_should_return_success();
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_free_sale_should_have_amount_zero(): array
    {
        return parent::purchase_initiating_free_sale_should_have_amount_zero();
    }

    /**
     * @test
     * @depends purchase_initiating_should_return_success
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function returned_fraud_advice_should_be_false(array $response): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->assertEquals(self::NO_FRAUD, $response['fraudAdvice']);
            return;
        }
        $this->assertEquals(self::NO_FRAUD, $response['fraudAdvice']);
        $this->assertEquals(FraudRecommendation::createDefaultAdvice()->toArray(), $response['fraudRecommendation']);
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_should_return_bad_request_when_payment_type_is_unsupported(): array
    {
        return parent::purchase_initiating_should_return_bad_request_when_payment_type_is_unsupported();
    }
}
