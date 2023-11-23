<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\CannotMarkNonCrossSaleItemAsSelectedCrossSaleException;

class InitializedItem
{
    /**
     * @var ItemId
     */
    private $itemId;

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
     * @var TaxInformation|null
     */
    private $taxInformation;

    /**
     * @var bool
     */
    private $isCrossSale;

    /**
     * @var bool
     */
    private $isCrossSaleSelected;

    /**
     * @var bool
     */
    private $isTrial;

    /**
     * @var string|null
     */
    private $subscriptionId;

    /**
     * @var
     */
    private $isNSFSupported;

    /**
     * @var TransactionCollection
     */
    private $transactionCollection;

    /**
     * InitializedItem constructor.
     *
     * @param Id                  $itemId              The item id
     * @param SiteId              $siteId              The site id
     * @param BundleId            $bundleId            The bundle id
     * @param AddonId             $addonId             The addon id
     * @param ChargeInformation   $chargeInformation   The charge information
     * @param TaxInformation|null $taxInformation      The tax information
     * @param bool                $isCrossSale         The cross sale flag
     * @param bool                $isTrial             The trial flag
     * @param string|null         $subscriptionId      Subscription Id
     * @param bool                $isCrossSaleSelected The selected cross sale flag
     * @param bool                $isNSFSupported      Flag to show if NSF is supported or not
     *
     * @throws \Exception
     */
    private function __construct(
        Id $itemId,
        SiteId $siteId,
        BundleId $bundleId,
        AddonId $addonId,
        ChargeInformation $chargeInformation,
        ?TaxInformation $taxInformation,
        bool $isCrossSale,
        bool $isTrial,
        ?string $subscriptionId,
        bool $isCrossSaleSelected = false,
        bool $isNSFSupported = false
    ) {
        $this->itemId                = $itemId;
        $this->siteId                = $siteId;
        $this->bundleId              = $bundleId;
        $this->addonId               = $addonId;
        $this->chargeInformation     = $chargeInformation;
        $this->taxInformation        = $taxInformation;
        $this->isCrossSale           = $isCrossSale;
        $this->isTrial               = $isTrial;
        $this->transactionCollection = new TransactionCollection();
        $this->isCrossSaleSelected   = $isCrossSaleSelected;
        $this->isNSFSupported        = $isNSFSupported;

        $this->initSubscriptionId($subscriptionId);
    }

    /**
     * InitializedItem constructor.
     *
     * @param SiteId              $siteId              The site id
     * @param BundleId            $bundleId            The bundle id
     * @param AddonId             $addonId             The addon id
     * @param ChargeInformation   $chargeInformation   The charge information
     * @param TaxInformation|null $taxInformation      The tax information
     * @param bool                $isCrossSale         The cross sale flag
     * @param bool                $isTrial             The trial flag
     * @param string|null         $subscriptionId      Subscription Id
     * @param bool                $isCrossSaleSelected The selected cross sale flag
     * @param bool                $isNSFSupported      Flag to show if NSF is supported or not
     *
     * @return self
     * @throws \Exception
     */
    public static function create(
        SiteId $siteId,
        BundleId $bundleId,
        AddonId $addonId,
        ChargeInformation $chargeInformation,
        ?TaxInformation $taxInformation,
        bool $isCrossSale,
        bool $isTrial,
        ?string $subscriptionId,
        bool $isCrossSaleSelected = false,
        bool $isNSFSupported = false
    ): self {
        return new static(
            ItemId::create(),
            $siteId,
            $bundleId,
            $addonId,
            $chargeInformation,
            $taxInformation,
            $isCrossSale,
            $isTrial,
            $subscriptionId,
            $isCrossSaleSelected,
            $isNSFSupported
        );
    }

    /**
     * InitializedItem constructor.
     *
     * @param ItemId              $itemId              The item id
     * @param SiteId              $siteId              The site id
     * @param BundleId            $bundleId            The bundle id
     * @param AddonId             $addonId             The addon id
     * @param ChargeInformation   $chargeInformation   The charge information
     * @param TaxInformation|null $taxInformation      The tax information
     * @param bool                $isCrossSale         The cross sale flag
     * @param bool                $isTrial             The trial flag
     * @param string|null         $subscriptionId      Subscription Id
     * @param bool                $isCrossSaleSelected Selected Cross sale flag
     * @param bool                $isNSFSupported      Flag to show if NSF is supported or not
     *
     * @return self
     * @throws \Exception
     */
    public static function restore(
        ItemId $itemId,
        SiteId $siteId,
        BundleId $bundleId,
        AddonId $addonId,
        ChargeInformation $chargeInformation,
        ?TaxInformation $taxInformation,
        bool $isCrossSale,
        bool $isTrial,
        ?string $subscriptionId,
        bool $isCrossSaleSelected = false,
        bool $isNSFSupported = false
    ): self {
        return new static(
            $itemId,
            $siteId,
            $bundleId,
            $addonId,
            $chargeInformation,
            $taxInformation,
            $isCrossSale,
            $isTrial,
            $subscriptionId,
            $isCrossSaleSelected,
            $isNSFSupported
        );
    }

    /**
     * @return ItemId
     */
    public function itemId(): ItemId
    {
        return $this->itemId;
    }

    /**
     * @return SiteId
     */
    public function siteId(): SiteId
    {
        return $this->siteId;
    }

    /**
     * @return BundleId
     */
    public function bundleId(): BundleId
    {
        return $this->bundleId;
    }

    /**
     * @return AddonId
     */
    public function addonId(): AddonId
    {
        return $this->addonId;
    }

    /**
     * @return ChargeInformation
     */
    public function chargeInformation(): ChargeInformation
    {
        return $this->chargeInformation;
    }

    /**
     * @return TaxInformation|null
     */
    public function taxInformation(): ?TaxInformation
    {
        return $this->taxInformation;
    }

    /**
     * @return bool
     */
    public function isCrossSale(): bool
    {
        return $this->isCrossSale;
    }

    /**
     * @return bool
     */
    public function isSelectedCrossSale(): bool
    {
        return $this->isCrossSaleSelected;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function markCrossSaleAsSelected(): void
    {
        if (!$this->isCrossSale()) {
            throw  new CannotMarkNonCrossSaleItemAsSelectedCrossSaleException();
        }
        $this->isCrossSaleSelected = true;
    }

    /**
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->isTrial;
    }

    /**
     * @return string|null
     */
    public function subscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    /**
     * Use value stored in db if exists or, if not, will create a new value and add it to $this->subscriptionId
     * and then return instance of SubscriptionId
     * @return SubscriptionId
     * @throws \Exception
     */
    public function buildSubscriptionId(): SubscriptionId
    {
        if (!$this->subscriptionId()) {
            $this->subscriptionId = (string) SubscriptionId::create();
        }

        return SubscriptionId::createFromString($this->subscriptionId());
    }

    /**
     * @return TransactionCollection
     */
    public function transactionCollection(): TransactionCollection
    {
        return $this->transactionCollection;
    }

    /**
     * @return bool
     */
    public function wasItemPurchaseSuccessful(): bool
    {
        return $this->transactionCollection()->lastState() === Transaction::STATUS_APPROVED;
    }

    /**
     * @return bool
     */
    public function wasItemNsfPurchase(): bool
    {
        /**
         * @var Transaction $lastTransaction
         */
        $lastTransaction = $this->lastTransaction();

        if (is_null($lastTransaction) || $lastTransaction->state() !== Transaction::STATUS_DECLINED) {
            return false;
        }

        return (bool) $lastTransaction->isNsf();
    }

    /**
     * @return bool
     */
    public function wasItemPurchasePending(): bool
    {
        return $this->transactionCollection()->lastState() === Transaction::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function wasItemPurchaseSuccessfulOrPending(): bool
    {
        $states = [
            Transaction::STATUS_APPROVED,
            Transaction::STATUS_PENDING
        ];

        return in_array($this->transactionCollection()->lastState(), $states, true);
    }

    /**
     * @return mixed|null
     */
    public function lastTransaction()
    {
        if (!$this->transactionCollection()->count()) {
            return null;
        }

        return $this->transactionCollection()->last();
    }

    /**
     * @return TransactionId|null
     */
    public function lastTransactionId(): ?TransactionId
    {
        if (!$this->transactionCollection()->count()) {
            return null;
        }

        return $this->transactionCollection()->last()->transactionId();
    }

    /**
     * @return string|null
     */
    public function billerName(): ?string
    {
        if (!$this->transactionCollection()->count()) {
            return null;
        }

        return $this->transactionCollection()->last()->billerName();
    }

    /**
     * @return string|null
     */
    public function lastTransactionState(): ?string
    {
        return $this->transactionCollection()->lastState();
    }

    /**
     * @param string|null $subscriptionId Subscription Id
     *
     * @return void
     */
    private function initSubscriptionId(?string $subscriptionId): void
    {
        $this->subscriptionId = null;

        if (!empty($subscriptionId)) {
            $this->subscriptionId = $subscriptionId;
        }
    }

    /**
     * @return ErrorClassification|null
     */
    public function errorClassification(): ?ErrorClassification
    {
        return ($this->lastTransaction()) ? $this->lastTransaction()->errorClassification() : null;
    }

    /**
     * @return void
     */
    public function resetTransactionCollection(): void
    {
        $this->transactionCollection = new TransactionCollection();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $rebillAmount = null;
        $rebillDays   = null;
        if ($this->chargeInformation() instanceof BundleRebillChargeInformation) {
            $rebillDays   = $this->chargeInformation()->repeatEvery()->days();
            $rebillAmount = $this->chargeInformation()->rebillAmount()->value();
        }

        $taxInformation = [];
        if (!is_null($this->taxInformation())) {
            $taxInformation = $this->taxInformation()->toArray();
        }

        $transactionCollection = [];
        if (!empty($this->transactionCollection())) {
            /** @var Transaction $transaction */
            foreach ($this->transactionCollection() as $transaction) {
                $transactionItem = [
                    'state'               => $transaction->state(),
                    'transactionId'       => (string) $transaction->transactionId(),
                    'newCCUsed'           => $transaction->newCCUsed(),
                    'billerName'          => $transaction->billerName(),
                    'acs'                 => (string) $transaction->acs(),
                    'pareq'               => (string) $transaction->pareq(),
                    'redirectUrl'         => $transaction->redirectUrl(),
                    'isNsf'               => $transaction->isNsf(),
                    'deviceCollectionUrl' => $transaction->deviceCollectionUrl(),
                    'deviceCollectionJwt' => $transaction->deviceCollectionJwt(),
                    'deviceFingerprintId' => $transaction->deviceFingerprintId(),
                    'threeDStepUpUrl'     => $transaction->threeDStepUpUrl(),
                    'threeDStepUpJwt'     => $transaction->threeDStepUpJwt(),
                    'md'                  => $transaction->md(),
                    'threeDFrictionless'  => $transaction->threeDFrictionless(),
                    'threeDVersion'       => $transaction->threeDVersion()
                ];

                // Add error classification only for the cases where is not null.
                if (!empty($transaction->errorClassification())) {
                    $transactionItem['errorClassification'] = $transaction->errorClassification()->toArray();
                }

                $transactionCollection[] = $transactionItem;
            }
        }

        $tax = array_merge(
            $this->chargeInformation()->fullTaxBreakDownArray(),
            $taxInformation
        );

        return [
            'itemId'                => (string) $this->itemId(),
            'addonId'               => (string) $this->addonId(),
            'bundleId'              => (string) $this->bundleId(),
            'siteId'                => (string) $this->siteId(),
            'subscriptionId'        => $this->subscriptionId(),
            'initialDays'           => $this->chargeInformation()->validFor()->days(),
            'rebillDays'            => $rebillDays,
            'initialAmount'         => $this->chargeInformation()->initialAmount()->value(),
            'rebillAmount'          => $rebillAmount,
            'tax'                   => !empty($tax) ? $tax : null,
            'transactionCollection' => $transactionCollection,
            'isTrial'               => $this->isTrial(),
            'isCrossSale'           => $this->isCrossSale(),
            'isCrossSaleSelected'   => $this->isSelectedCrossSale(),
            'isNSFSupported'        => $this->isNSFSupported(),

        ];
    }

    /**
     * @param bool $isNSFSupported
     *
     * @reture void
     */
    public function setIsNSFSupported(bool $isNSFSupported): void
    {
        $this->isNSFSupported = $isNSFSupported;
    }

    /**
     * @return bool
     */
    public function isNSFSupported() : bool
    {
        return $this->isNSFSupported;
    }
}
