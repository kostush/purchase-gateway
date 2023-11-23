<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use Tests\UnitTestCase;

class FraudRecommendationCollectionTest extends UnitTestCase
{

    /**
     * @param int    $code
     * @param string $severity
     * @return array
     */
    private function createFraudCollectionArrayNode(int $code, string $severity): array
    {
        return [
            'code'     => $code,
            'severity' => $severity,
            'message'  => 'message'
        ];
    }

    /**
     * @test
     */
    public function it_should_create_fraud_recommendation_from_array(): void
    {
        $fraudRecommendationCollectionArray = [
            0 => $this->createFraudCollectionArrayNode(100,'block')
        ];

        $fraudCollection = FraudRecommendationCollection::createFromArray($fraudRecommendationCollectionArray);
        $this->assertInstanceOf(FraudRecommendationCollection::class, $fraudCollection);
    }

    /**
     * @test
     */
    public function it_should_not_hard_block_when_there_is_only_soft_blocks_captcha_or_3ds_on_collection(): void
    {
        $fraudRecommendationCollection = [
            0 => $this->createFraudCollectionArrayNode(200,'block'),
            1 => $this->createFraudCollectionArrayNode(300,'block'),
            2 => $this->createFraudCollectionArrayNode(1000,'allow'),
        ];

        $fraudCollection = FraudRecommendationCollection::createFromArray($fraudRecommendationCollection);
        $this->assertFalse($fraudCollection->hasHardBlock());
    }

    /**
     * @test
     */
    public function it_should_hard_block_when_there_is_at_least_one_block_non_captcha_nor_3ds(): void
    {
        $fraudRecommendationCollection = [
            0 => $this->createFraudCollectionArrayNode(200,'block'),
            1 => $this->createFraudCollectionArrayNode(300,'block'),
            2 => $this->createFraudCollectionArrayNode(100,'block'),
            3 => $this->createFraudCollectionArrayNode(1000,'allow'),
        ];

        $fraudCollection = FraudRecommendationCollection::createFromArray($fraudRecommendationCollection);
        $this->assertTrue($fraudCollection->hasHardBlock());
    }
}
