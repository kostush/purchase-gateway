<?php

namespace Tests\System\Mgpg\InitPurchase;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;

/**
 * @group InitPurchase
 */
class WithCrossSellNoFraudTest extends InitPurchase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        // Add them to repo
        $this->createAndAddBundleToRepository(
            [
                'addonId'  => InitPurchase::ADDON_ID,
                'bundleId' => InitPurchase::BUNDLE_ID,
            ]
        );

        $this->payload['bundleId'] = InitPurchase::BUNDLE_ID;
        $this->payload['addonId']  = InitPurchase::ADDON_ID;
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
}
