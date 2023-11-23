<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class ProcessedBundleItem extends BaseProcessedItem
{
    /**
     * @var SubscriptionInfo
     */
    private $subscriptionInfo;

    /**
     * @var BundleId
     */
    private $bundleId;

    /**
     * @var AddonCollection
     */
    private $addonCollection;

    /**
     * @var bool
     */
    private $isCrossSale;

    /**
     * ProcessedBundleItem constructor.
     * @param SubscriptionInfo      $subscriptionInfo      Subscription Info
     * @param ItemId                $itemId                Item Id
     * @param TransactionCollection $transactionCollection Transaction Collection
     * @param BundleId              $bundleId              Bundle Id
     * @param AddonCollection       $addonCollection       Addon collection
     * @param bool|null             $isCrossSale           The cross sale flag
     */
    protected function __construct(
        ?SubscriptionInfo $subscriptionInfo,
        ItemId $itemId,
        TransactionCollection $transactionCollection,
        BundleId $bundleId,
        AddonCollection $addonCollection,
        ?bool $isCrossSale
    ) {
        $this->subscriptionInfo      = $subscriptionInfo;
        $this->itemId                = $itemId;
        $this->transactionCollection = $transactionCollection;
        $this->bundleId              = $bundleId;
        $this->addonCollection       = $addonCollection;
        $this->isCrossSale           = $isCrossSale;
    }

    /**
     * @param SubscriptionInfo      $subscriptionInfo      Subscription Info
     * @param ItemId                $itemId                Item Id
     * @param TransactionCollection $transactionCollection Transaction Collection
     * @param BundleId              $bundleId              Bundle Id
     * @param AddonCollection       $addonCollection       Addon collection
     * @param bool|null             $isCrossSale           The cross sale flag
     * @return ProcessedBundleItem
     */
    public static function create(
        ?SubscriptionInfo $subscriptionInfo,
        ItemId $itemId,
        TransactionCollection $transactionCollection,
        BundleId $bundleId,
        AddonCollection $addonCollection,
        ?bool $isCrossSale = null
    ): self {
        return new static(
            $subscriptionInfo,
            $itemId,
            $transactionCollection,
            $bundleId,
            $addonCollection,
            $isCrossSale
        );
    }

    /**
     * @return SubscriptionInfo
     */
    public function subscriptionInfo(): ?SubscriptionInfo
    {
        return $this->subscriptionInfo;
    }

    /**
     * @return BundleId
     */
    public function bundleId(): BundleId
    {
        return $this->bundleId;
    }

    /**
     * @return AddonCollection
     */
    public function addonCollection(): AddonCollection
    {
        return $this->addonCollection;
    }

    /**
     * @return bool
     */
    public function isCrossSale(): bool
    {
        return $this->isCrossSale;
    }

    /**
     * @return TransactionId|null
     */
    public function retrieveTransactionId(): ?TransactionId
    {
        if (!$this->transactionCollection()->last() instanceof Transaction) {
            return null;
        }

        // retrieve the transaction id of the last transaction
        return $this->transactionCollection()->last()->transactionId();
    }

    /**
     * @param TransactionCollection $transactionCollection Transaction collection
     * @return void
     */
    public function updateTransactionCollection(TransactionCollection $transactionCollection): void
    {
        $this->transactionCollection = $transactionCollection;
    }

    /**
     * @param bool $isCrossSale Is cross sale
     * @return void
     */
    public function setIsCrossSale(bool $isCrossSale): void
    {
        $this->isCrossSale = $isCrossSale;
    }
}
