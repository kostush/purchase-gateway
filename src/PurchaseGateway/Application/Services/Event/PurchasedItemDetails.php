<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

abstract class PurchasedItemDetails
{
    /**
     * @var string
     */
    protected $bundleId;

    /**
     * @var string
     */
    protected $addOnId;

    /**
     * @var array|null
     */
    protected $tax;

    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var string|null
     */
    protected $billerTransactionId;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var \DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @var float|null
     */
    protected $rebillAmount;

    /**
     * @var int|null
     */
    protected $rebillFrequency;

    /**
     * @var int|null
     */
    protected $rebillStart;

    /**
     * @var string|null
     */
    protected $subscriptionId;

    /**
     * @var string|null
     */
    protected $parentSubscription;

    /**
     * @var bool
     */
    protected $isTrial;

    /**
     * @var bool
     */
    protected $isUnlimited;

    /**
     * @var bool
     */
    protected $isNsf;

    /**
     * @var boolean
     */
    protected $isDisabled;

    /**
     * @var boolean
     */
    protected $isExpired;

    /**
     * @var boolean
     */
    protected $isPrepaid;

    /**
     * @var boolean
     */
    protected $isLowRisk;

    /**
     * @var bool
     */
    protected $isMigrated;

    /**
     * @var bool
     */
    protected $requireActiveContent;

    /**
     * @var string
     */
    protected $status;

    /**
     * PurchasedItemDetails constructor.
     * @param string             $bundleId             Bundle id
     * @param string             $addOnId              Addon id
     * @param array|null         $tax                  Tax array
     * @param string             $siteId               Site id
     * @param string             $transactionId        Transaction id
     * @param string|null        $billerTransactionId  Biller transaction id
     * @param string             $itemId               Item id
     * @param float              $amount               Amount
     * @param \DateTimeImmutable $createdAt            Created at
     * @param float|null         $rebillAmount         Rebill amount
     * @param int|null           $rebillFrequency      Rebill Frequency
     * @param int|null           $rebillStart          Rebill start
     * @param string|null        $subscriptionId       Subscription id
     * @param string|null        $parentSubscription   Parent subscription
     * @param bool               $isTrial              Is trial
     * @param bool               $isUnlimited          Is unlimited
     * @param bool               $isNsf                Is nsf
     * @param bool               $isDisabled           Is disabled
     * @param bool               $isExpired            Is expired
     * @param bool               $isPrepaid            Is prepaid
     * @param bool               $isLowRisk            Is low risk
     * @param bool               $isMigrated           Is migrated
     * @param bool               $requireActiveContent Require active content
     * @param string             $status               Purchase status
     */
    protected function __construct(
        string $bundleId,
        string $addOnId,
        ?array $tax,
        string $siteId,
        string $transactionId,
        ?string $billerTransactionId,
        string $itemId,
        float $amount,
        \DateTimeImmutable $createdAt,
        ?float $rebillAmount,
        ?int $rebillFrequency,
        ?int $rebillStart,
        ?string $subscriptionId,
        ?string $parentSubscription,
        bool $isTrial,
        bool $isUnlimited,
        bool $isNsf,
        bool $isDisabled,
        bool $isExpired,
        bool $isPrepaid,
        bool $isLowRisk,
        bool $isMigrated,
        bool $requireActiveContent,
        string $status
    ) {
        $this->bundleId             = $bundleId;
        $this->addOnId              = $addOnId;
        $this->tax                  = $tax;
        $this->siteId               = $siteId;
        $this->transactionId        = $transactionId;
        $this->billerTransactionId  = $billerTransactionId;
        $this->itemId               = $itemId;
        $this->amount               = $amount;
        $this->createdAt            = $createdAt;
        $this->rebillAmount         = $rebillAmount;
        $this->rebillFrequency      = $rebillFrequency;
        $this->rebillStart          = $rebillStart;
        $this->subscriptionId       = $subscriptionId;
        $this->parentSubscription   = $parentSubscription;
        $this->isTrial              = $isTrial;
        $this->isUnlimited          = $isUnlimited;
        $this->isNsf                = $isNsf;
        $this->isDisabled           = $isDisabled;
        $this->isExpired            = $isExpired;
        $this->isPrepaid            = $isPrepaid;
        $this->isLowRisk            = $isLowRisk;
        $this->isMigrated           = $isMigrated;
        $this->requireActiveContent = $requireActiveContent;
        $this->status               = $status;
    }

    /**
     * @param float      $initialAmount Initial amount
     * @param float|null $rebillAmount  Rebill amount
     * @return array
     */
    public function createTaxPayloadFromAmounts(float $initialAmount, ?float $rebillAmount): array
    {
        $tax = [
            'initialAmount' => [
                'beforeTaxes' => $initialAmount,
                'taxes' => 0,
                'afterTaxes' => $initialAmount,
            ],
            'rebillAmount' => null
        ];
        if (!empty($rebillAmount)) {
            $tax['rebillAmount'] = [
                'beforeTaxes' => $rebillAmount,
                'taxes' => 0,
                'afterTaxes' => $rebillAmount,
            ];
        }

        return $tax;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
