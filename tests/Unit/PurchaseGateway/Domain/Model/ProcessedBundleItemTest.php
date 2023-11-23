<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\UnitTestCase;

class ProcessedBundleItemTest extends UnitTestCase
{
    /**
     * @test
     * @return ProcessedBundleItem
     * @throws \Exception
     */
    public function it_should_return_a_processed_bundle_item_object(): ProcessedBundleItem
    {
        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            RocketgateBiller::BILLER_NAME,
            null
        );
        $transactions->add($transaction);
        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));
        $result = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($this->faker->uuid),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        $this->assertInstanceOf(ProcessedBundleItem::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_contain_a_subscription_info_object(ProcessedBundleItem $processedBundleItem): void
    {
        $this->assertInstanceOf(SubscriptionInfo::class, $processedBundleItem->subscriptionInfo());
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_contain_an_item_id_object(ProcessedBundleItem $processedBundleItem): void
    {
        $this->assertInstanceOf(ItemId::class, $processedBundleItem->itemId());
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_contain_a_transaction_collection_object(ProcessedBundleItem $processedBundleItem): void
    {
        $this->assertInstanceOf(TransactionCollection::class, $processedBundleItem->transactionCollection());
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_contain_a_bundle_id_object(ProcessedBundleItem $processedBundleItem): void
    {
        $this->assertInstanceOf(BundleId::class, $processedBundleItem->bundleId());
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_contain_an_add_on_collection_object(ProcessedBundleItem $processedBundleItem): void
    {
        $this->assertInstanceOf(AddonCollection::class, $processedBundleItem->addonCollection());
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_set_is_cross_sale_to_true(ProcessedBundleItem $processedBundleItem): void
    {
        $processedBundleItem->setIsCrossSale(true);
        $this->assertTrue($processedBundleItem->isCrossSale());
    }

    /**
     * @test
     * @depends it_should_return_a_processed_bundle_item_object
     * @param ProcessedBundleItem $processedBundleItem Processed Bundle Item Object
     * @return void
     */
    public function it_should_set_is_cross_sale_to_false(ProcessedBundleItem $processedBundleItem): void
    {
        $processedBundleItem->setIsCrossSale(false);
        $this->assertFalse($processedBundleItem->isCrossSale());
    }
}
