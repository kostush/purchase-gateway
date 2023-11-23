<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\UnitTestCase;

class InitializedItemTest extends UnitTestCase
{
    /**
     * @var SiteId
     */
    private $siteId;

    /**
     * @var BundleId
     */
    private $bundleId;

    /**
     * @var AddonId
     */
    private $addonId;

    /**
     * @var ChargeInformation
     */
    private $chargeInformation;

    /**
     * @var TaxInformation
     */
    private $taxInformation;

    /**
     * @var bool
     */
    private $isCrossSale;

    /**
     * @var bool
     */
    private $isTrial;

    /**
     * @var bool
     */
    private $isCrossSaleSelected;

    /**
     * @var bool
     */
    private $isNSFSupported;

    /**
     * @var ItemId
     */
    private $itemId;

    /**
     * Setup function, called before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->siteId              = SiteId::create();
        $this->bundleId            = BundleId::create();
        $this->addonId             = AddonId::create();
        $this->itemId              = ItemId::create();
        $this->chargeInformation   = $this->createMock(BundleRebillChargeInformation::class);
        $this->isCrossSale         = true;
        $this->isTrial             = true;
        $this->isCrossSaleSelected = false;
        $this->isNSFSupported      = false;

        $this->chargeInformation->method('validFor')->willReturn(Duration::create(30));
        $this->taxInformation = $this->createMock(TaxInformation::class);
    }

    /**
     * @test
     * @throws \Exception
     * @return InitializedItem
     */
    public function it_should_return_an_initialize_item_object(): InitializedItem
    {
        $result = InitializedItem::create(
            $this->siteId,
            $this->bundleId,
            $this->addonId,
            $this->chargeInformation,
            $this->taxInformation,
            $this->isCrossSale,
            $this->isTrial,
            '',
            $this->isCrossSaleSelected,
            $this->isNSFSupported
        );

        $this->assertInstanceOf(InitializedItem::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_site_id_property(InitializedItem $initializedItem): void
    {
        $this->assertInstanceOf(SiteId::class, $initializedItem->siteId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_bundle_id_property(InitializedItem $initializedItem): void
    {
        $this->assertInstanceOf(BundleId::class, $initializedItem->bundleId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_addon_id_property(InitializedItem $initializedItem): void
    {
        $this->assertInstanceOf(AddonId::class, $initializedItem->addonId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_charge_information_property(InitializedItem $initializedItem): void {
        $this->assertInstanceOf(ChargeInformation::class, $initializedItem->chargeInformation());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_tax_information_property(InitializedItem $initializedItem): void
    {
        $this->assertInstanceOf(TaxInformation::class, $initializedItem->taxInformation());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_is_cross_sale_flag(InitializedItem $initializedItem): void
    {
        $this->assertTrue($initializedItem->isCrossSale());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function it_should_return_an_object_with_a_is_trial_flag(InitializedItem $initializedItem): void
    {
        $this->assertTrue($initializedItem->isTrial());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function subscription_id_should_return_a_null_value(InitializedItem $initializedItem): void
    {
        $this->assertNull($initializedItem->subscriptionId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_non_empty_subscription_id_after_build_subscription_id_is_called(
        InitializedItem $initializedItem
    ): void {
        $initializedItem->buildSubscriptionId();
        $this->assertNotEmpty($initializedItem->subscriptionId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     * @throws \Exception
     */
    public function subscription_id_should_maintain_the_initial_value_on_repeated_build_subscription_id_calls(
        InitializedItem $initializedItem
    ): void {
        $initialSubscriptionId = $initializedItem->subscriptionId();

        $initializedItem->buildSubscriptionId();
        $initializedItem->buildSubscriptionId();

        $this->assertSame($initialSubscriptionId, $initializedItem->subscriptionId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     * @throws \Exception
     */
    public function last_transaction_id_should_be_null_when_no_transactions_found(
        InitializedItem $initializedItem
    ): void {
        $this->assertEmpty($initializedItem->lastTransactionId());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     * @throws \Exception
     */
    public function last_transaction_should_be_null_when_no_transactions_found(
        InitializedItem $initializedItem
    ): void {
        $this->assertEmpty($initializedItem->lastTransaction());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return InitializedItem
     * @throws \Exception
     */
    public function last_transaction_id_should_contain_last_transactions_collection_id(
        InitializedItem $initializedItem
    ): InitializedItem {
        $firstTransactionId = TransactionId::createFromString($this->faker->uuid);
        $firstTransaction   = $this->createMock(Transaction::class);
        $firstTransaction->method('transactionId')->willReturn($firstTransactionId);
        $initializedItem->transactionCollection()->add($firstTransaction);

        $secondTransactionId = TransactionId::createFromString($this->faker->uuid);
        $secondTransaction   = $this->createMock(Transaction::class);
        $secondTransaction->method('transactionId')->willReturn($secondTransactionId);
        $secondTransaction->method('state')->willReturn(Transaction::STATUS_APPROVED);
        $initializedItem->transactionCollection()->add($secondTransaction);

        $this->assertInstanceOf(TransactionId::class, $initializedItem->lastTransactionId());
        $this->assertEquals((string) $secondTransactionId, (string) $initializedItem->lastTransactionId());

        return $initializedItem;
    }

    /**
     * @test
     * @depends last_transaction_id_should_contain_last_transactions_collection_id
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     * @throws \Exception
     */
    public function last_transaction_should_contain_last_transaction_object(
        InitializedItem $initializedItem
    ): void {
        $this->assertInstanceOf(Transaction::class, $initializedItem->lastTransaction());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function last_transaction_state_should_contain_last_transactions_collection_state_approved(
        InitializedItem $initializedItem
    ): void {
        $this->assertSame(Transaction::STATUS_APPROVED, $initializedItem->lastTransactionState());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function was_item_purchase_successful_should_evaluate_to_true(
        InitializedItem $initializedItem
    ): void {
        $this->assertTrue($initializedItem->wasItemPurchaseSuccessful());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function was_item_purchase_successful_or_pending_should_evaluate_to_true_if_transaction_state_is_approved_or_pending(
        InitializedItem $initializedItem
    ): void {
        $this->assertTrue($initializedItem->wasItemPurchaseSuccessfulOrPending());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem The initialized item
     * @return void
     */
    public function to_array_should_return_an_array_with_all_the_class_property_values(
        InitializedItem $initializedItem
    ): void {
        $array = $initializedItem->toArray();

        $this->assertArrayHasKey('addonId', $array);
        $this->assertArrayHasKey('bundleId', $array);
        $this->assertArrayHasKey('siteId', $array);
        $this->assertArrayHasKey('initialDays', $array);
        $this->assertArrayHasKey('rebillDays', $array);
        $this->assertArrayHasKey('initialAmount', $array);
        $this->assertArrayHasKey('rebillAmount', $array);
        $this->assertArrayHasKey('tax', $array);
        $this->assertArrayHasKey('isTrial', $array);
        $this->assertArrayHasKey('isCrossSale', $array);
        $this->assertArrayHasKey('subscriptionId', $array);
        $this->assertArrayHasKey('isCrossSaleSelected', $array);
        $this->assertArrayHasKey('transactionCollection', $array);
        $this->assertArrayHasKey('isNSFSupported', $array);
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     * @param InitializedItem $initializedItem
     * @throws \Exception
     */
    public function it_should_allow_transaction_collection_to_be_reset(InitializedItem $initializedItem): void
    {
        $firstTransactionId = TransactionId::createFromString($this->faker->uuid);
        $firstTransaction = $this->createMock(Transaction::class);
        $firstTransaction->method('transactionId')->willReturn($firstTransactionId);

        $initializedItem->transactionCollection()->add($firstTransaction);

        $initializedItem->resetTransactionCollection();

        self::assertTrue($initializedItem->transactionCollection()->isEmpty());
    }

    /**
     * @test
     * @depends it_should_return_an_initialize_item_object
     *
     * @param InitializedItem $initializedItem The initialized item
     *
     * @return void
     */
    public function isNSFSupported_should_return_a_false_value(InitializedItem $initializedItem): void
    {
        $this->assertFalse($this->isNSFSupported);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function create_should_have_isNSFSupported_default_false_if_none_passed(): void
    {
        $result = InitializedItem::create(
            $this->siteId,
            $this->bundleId,
            $this->addonId,
            $this->chargeInformation,
            $this->taxInformation,
            $this->isCrossSale,
            $this->isTrial,
            '',
            $this->isCrossSaleSelected
        );

        $this->assertFalse($result->isNSFSupported());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function create_should_have_isNSFSupported_values_as_the_given_one(): void
    {
        $result = InitializedItem::create(
            $this->siteId,
            $this->bundleId,
            $this->addonId,
            $this->chargeInformation,
            $this->taxInformation,
            $this->isCrossSale,
            $this->isTrial,
            '',
            $this->isCrossSaleSelected,
            true
        );

        $this->assertTrue($result->isNSFSupported());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function restore_should_have_isNSFSupported_default_false_if_none_passed(): void
    {
        $result = InitializedItem::restore(
            $this->itemId,
            $this->siteId,
            $this->bundleId,
            $this->addonId,
            $this->chargeInformation,
            $this->taxInformation,
            $this->isCrossSale,
            $this->isTrial,
            null
        );

        $this->assertFalse($result->isNSFSupported());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function restore_should_have_isNSFSupported_values_as_the_given_one(): void
    {
        $result = InitializedItem::restore(
            $this->itemId,
            $this->siteId,
            $this->bundleId,
            $this->addonId,
            $this->chargeInformation,
            $this->taxInformation,
            $this->isCrossSale,
            $this->isTrial,
            null,
            $this->isCrossSaleSelected,
            true
        );

        $this->assertTrue($result->isNSFSupported());
    }
}
