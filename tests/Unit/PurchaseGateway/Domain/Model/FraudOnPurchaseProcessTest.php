<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use Tests\UnitTestCase;

class FraudOnPurchaseProcessTest extends UnitTestCase
{
    const UUID = '11973b24-b381-11e9-a2a3-2a2ae2dbcce4';

    /**
     * @return PurchaseProcess
     * @throws Exception
     */
    private function returnPurchaseProcess(): PurchaseProcess
    {
        $itemsCollection = new InitializedItemCollection();
        $itemsCollection->offsetSet(self::UUID, $this->createMock(InitializedItem::class));

        $purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(PaymentInfo::class),
            new InitializedItemCollection(),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            null
        );

        return $purchaseProcess;
    }

    /**
     * This scenario is to cover blacklist on process, velocity (init/process) or
     * other kind of severity block different from captcha and 3ds.
     * Black list doesn't have a second chance anymore
     * @test
     * @throws Exception
     */
    public function it_should_block_process_when_fraud_recommendation_collection_has_hard_block_even_not_blocking_on_fraud_advice(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('shouldBlockProcess')
            ->willReturn(false);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->shouldBlockProcess());
    }

    /**
     * This scenario is to cover when captcha is not validated
     * @test
     * @throws Exception
     */
    public function it_should_block_process_when_fraud_recommendation_collection_has_no_hard_block_but_blocking_on_fraud_advice(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(false);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        //Captcha or 3ds not validated
        $fraudAdvice
            ->expects($this->any())
            ->method('shouldBlockProcess')
            ->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->shouldBlockProcess());
    }

    /**
     * This scenario covers blacklist on init
     * @test
     * @throws Exception
     */
    public function it_should_block_process_when_fraud_recommendation_collection_has_hard_block_and_fraud_advice(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('shouldBlockProcess')
            ->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->shouldBlockProcess());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_is_fraud_when_it_is_black_list_on_init(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnProcess')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnInit')
            ->willReturn(true);

        $fraudAdvice
            ->expects($this->any())
            ->method('isCaptchaValidated')
            ->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_is_fraud_when_it_is_black_list_on_process(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnProcess')
            ->willReturn(true);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnInit')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isCaptchaValidated')
            ->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_is_fraud_when_captcha_is_not_validated(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(false);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnProcess')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnInit')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isCaptchaValidated')
            ->willReturn(false);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * This scenario cover velocity block or other kind of severity block that is not a captcha
     * @test
     * @throws Exception
     */
    public function it_is_fraud_when_there_is_a_fraud_hard_block_even_with_no_fraud_advice_block(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnProcess')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnInit')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isCaptchaValidated')
            ->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_is_not_fraud_when_there_is_no_hard_block_nor_captcha(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        /** @var FraudRecommendationCollection $fraudCollection */
        $fraudCollection = $this->createMock(FraudRecommendationCollection::class);

        $fraudCollection
            ->expects($this->any())
            ->method('hasHardBlock')
            ->willReturn(false);

        $fraudAdvice = $this->createMock(FraudAdvice::class);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnProcess')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isBlacklistedOnInit')
            ->willReturn(false);

        $fraudAdvice
            ->expects($this->any())
            ->method('isCaptchaValidated')
            ->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $this->assertFalse($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_is_not_hard_block_when_there_is_no_fraud_collection(): void
    {
        $purchaseProcess = $this->returnPurchaseProcess();

        $this->assertFalse($purchaseProcess->fraudHardBlock());
    }
}
