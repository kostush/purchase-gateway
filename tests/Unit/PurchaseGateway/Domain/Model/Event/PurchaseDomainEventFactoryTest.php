<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model\Event;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseDomainEventFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use Tests\UnitTestCase;

class PurchaseDomainEventFactoryTest extends UnitTestCase
{
    /** @var PurchaseProcess|MockObject */
    protected $purchaseProcess;

    /** @var Purchase|MockObject */
    protected $purchase;

    /**
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $chargeInformation = $this->createMock(BundleRebillChargeInformation::class);
        $chargeInformation->method('validFor')->willReturn(Duration::create(30));


        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('lastTransaction')->willReturn(
            Transaction::create(
                TransactionId::create(),
                Transaction::STATUS_APPROVED,
                'rocketgate'
            )
        );
        $initializedItem->method('toArray')->willReturn(
            [
                'itemId'                => $this->faker->uuid,
                'addonId'               => $this->faker->uuid,
                'bundleId'              => $this->faker->uuid,
                'siteId'                => $this->faker->uuid,
                'subscriptionId'        => $this->faker->uuid,
                'initialDays'           => 15,
                'rebillDays'            => 30,
                'initialAmount'         => 40,
                'rebillAmount'          => 99,
                'tax'                   => [],
                'transactionCollection' => [],
                'isTrial'               => false,
                'isCrossSale'           => false,
                'isCrossSaleSelected'   => false
            ]
        );

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $this->purchaseProcess->method('atlasFields')->willReturn(AtlasFields::create());
        $this->purchaseProcess->method('userInfo')->willReturn(
            UserInfo::create(CountryCode::create('US'), Ip::create('172.10.1.244'))
        );

        $initializedItem = $this->createMock(ProcessedBundleItem::class);
        $initializedItem->method('subscriptionInfo')->willReturn(
            $this->createMock(SubscriptionInfo::class)
        );

        $this->purchase = $this->createMock(Purchase::class);
        $this->purchase->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_purchase_processed_object_when_purchase_state_is_not_pending(): void
    {
        $domainEvent = PurchaseDomainEventFactory::create($this->purchaseProcess, $this->purchase);
        $this->assertInstanceOf(PurchaseProcessed::class, $domainEvent);
    }
}
