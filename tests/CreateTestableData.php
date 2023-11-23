<?php
declare(strict_types=1);

namespace Tests;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NetbillingCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

trait CreateTestableData
{
    /**
     * @return ProcessedBundleItem
     * @throws \Exception
     */
    public function createItemDatabaseRecord(): ProcessedBundleItem
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

        $item = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($this->faker->uuid),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        app('em')->persist($item);
        app('em')->flush();

        return $item;
    }

    /**
     * @return RocketgateCCRetrieveTransactionResult
     */
    public function createMockedTransaction(): RocketgateCCRetrieveTransactionResult
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn('23423');
        $retrieveTransaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getSiteId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getMerchantId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getInvoiceId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getCustomerId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getCurrency')->willReturn('RON');
        $retrieveTransaction->method('getPaymentType')->willReturn('cc');
        $retrieveTransaction->method('getMerchantPassword')->willReturn('password');
        $retrieveTransaction->method('getCardHash')->willReturn('123456789');
        $retrieveTransaction->method('getMerchantAccount')->willReturn('MerchantAccount');
        $retrieveTransaction->method('getBillerTransactions')->willReturn([]);

        $memberInformation = $this->createMock(MemberInformation::class);

        $ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $ccTransactionInformation->method('first6')->willReturn('123456');
        $ccTransactionInformation->method('last4')->willReturn('4444');
        $ccTransactionInformation->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);


        return new RocketgateCCRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            RocketgateBillerFields::create(
                $_ENV['ROCKETGATE_MERCHANT_ID_2'],
                $_ENV['ROCKETGATE_MERCHANT_PASSWORD_2'],
                '2037',
                'sharedSecret',
                true
            )
        );
    }

    /**
     * @return ProcessedBundleItem
     * @throws \Exception
     */
    public function createItemRecordForNetbilling(): ProcessedBundleItem
    {
        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            NetbillingBiller::BILLER_NAME,
            null
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $item = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($this->faker->uuid),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        return $item;
    }

    /**
     * @return NetbillingCCRetrieveTransactionResult
     */
    public function createNetbillingMockedTransaction(): NetbillingCCRetrieveTransactionResult
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn('23423');
        $retrieveTransaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getSiteId')->willReturn($this->faker->uuid);

        $retrieveTransaction->method('getCurrency')->willReturn('RON');
        $retrieveTransaction->method('getPaymentType')->willReturn('cc');
        $retrieveTransaction->method('getCardHash')->willReturn('99999999999');

        $retrieveTransaction->method('getBillerTransactions')->willReturn([]);

        $memberInformation = $this->createMock(MemberInformation::class);

        $ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $ccTransactionInformation->method('first6')->willReturn('123456');
        $ccTransactionInformation->method('last4')->willReturn('4444');
        $ccTransactionInformation->method('billerName')->willReturn(NetbillingBiller::BILLER_NAME);


        return new NetbillingCCRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            NetbillingBillerFields::create(
                'accountId',
                'siteTag',
                'binRouting',
                'merchantPassword'
            )
        );
    }

    /**
     * @return ProcessedItemsCollection
     * @throws \Exception
     */
    public function initProcessedItems(): ProcessedItemsCollection
    {
        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            'epoch'
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $processedBundleItem = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($this->faker->uuid),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        $processedItemsCollection = new ProcessedItemsCollection();
        $processedItemsCollection->offsetSet(
            $this->faker->uuid,
            $processedBundleItem
        );

        return $processedItemsCollection;
    }

    /**
     * @return ProcessedBundleItem
     * @throws \Exception
     */
    public function createItemRecordForEpoch(): ProcessedBundleItem
    {
        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            EpochBiller::BILLER_NAME,
            null,
            null,
            null,
            $this->faker->url
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $item = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($this->faker->uuid),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        return $item;
    }

    /**
     * @return EpochCCRetrieveTransactionResult
     */
    public function createEpochMockedTransaction(): EpochCCRetrieveTransactionResult
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn('23425');
        $retrieveTransaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getSiteId')->willReturn($this->faker->uuid);

        $retrieveTransaction->method('getCurrency')->willReturn('RON');
        $retrieveTransaction->method('getPaymentType')->willReturn('cc');

        $retrieveTransaction->method('getBillerTransactions')->willReturn([]);

        $memberInformation = $this->createMock(MemberInformation::class);

        $ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $ccTransactionInformation->method('billerName')->willReturn(EpochBiller::BILLER_NAME);


        return new EpochCCRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            EpochBillerFields::create(
                'clientId',
                'clientKey',
                'clientVerificationKey'
            )
        );
    }
}
