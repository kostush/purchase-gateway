<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI\Processed;

class SelectedCrossSell extends Base
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $subscriptionId;

    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $bundleId;

    /**
     * @var string
     */
    protected $addOnId;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var int
     */
    protected $initialDays;

    /**
     * @var float
     */
    protected $initialAmount;

    /**
     * @var int|null
     */
    protected $rebillDays;

    /**
     * @var float|null
     */
    protected $rebillAmount;

    /**
     * @var array|null
     */
    protected $tax;

    /**
     * @var bool|null
     */
    protected $isNsf;

    /**
     * @var float
     */
    protected $chargedAmountBeforeTaxes;

    /**
     * @var float
     */
    protected $chargedAmountAfterTaxes;

    /**
     * @var float|null
     */
    protected $chargedTaxAmount;

    /**
     * SelectedCrossSell constructor.
     *
     * @param string      $status         Status
     * @param string      $siteId         Site Id
     * @param string      $itemId         Item Id
     * @param string      $bundleId       Bundle Id
     * @param string      $addOnId        AddOn Id
     * @param int         $initialDays    Initial Days
     * @param float       $initialAmount  Initial Amount
     * @param int|null    $rebillDays     Rebill Days
     * @param float |null $rebillAmount   Rebill Amount
     * @param string|null $transactionId  Transaction Id
     * @param array|null  $tax            Tax
     * @param string|null $subscriptionId Subscription Id
     * @param bool|null   $isNsf          Is Nsf
     */
    private function __construct(
        string $status,
        string $siteId,
        string $itemId,
        string $bundleId,
        string $addOnId,
        int $initialDays,
        float $initialAmount,
        ?int $rebillDays,
        ?float $rebillAmount,
        ?string $transactionId,
        ?array $tax,
        ?string $subscriptionId,
        ?bool $isNsf
    ) {
        $this->status         = $status;
        $this->siteId         = $siteId;
        $this->itemId         = $itemId;
        $this->bundleId       = $bundleId;
        $this->addOnId        = $addOnId;
        $this->initialDays    = $initialDays;
        $this->initialAmount  = $initialAmount;
        $this->rebillDays     = $rebillDays;
        $this->rebillAmount   = $rebillAmount;
        $this->transactionId  = $transactionId;
        $this->tax            = $tax;
        $this->subscriptionId = $subscriptionId;
        $this->isNsf          = $isNsf;

        $this->setChargedTaxes();
    }

    /**
     * @param string      $status         Status
     * @param string      $siteId         Site Id
     * @param string      $itemId         Item Id
     * @param string      $bundleId       Bundle Id
     * @param string      $addOnId        AddOn Id
     * @param int         $initialDays    Initial Days
     * @param float       $initialAmount  Initial Amount
     * @param int|null    $rebillDays     Rebill Days
     * @param float|null  $rebillAmount   Rebill Amount
     * @param string|null $transactionId  Transaction Id
     * @param array|null  $tax            Tax
     * @param string|null $subscriptionId Subscription Id
     * @param bool|null   $isNsf          Is Nsf
     *
     * @return SelectedCrossSell
     */
    public static function create(
        string $status,
        string $siteId,
        string $itemId,
        string $bundleId,
        string $addOnId,
        int $initialDays,
        float $initialAmount,
        ?int $rebillDays,
        ?float $rebillAmount,
        ?string $transactionId,
        ?array $tax,
        ?string $subscriptionId,
        ?bool $isNsf
    ): self {
        return new static(
            $status,
            $siteId,
            $itemId,
            $bundleId,
            $addOnId,
            $initialDays,
            $initialAmount,
            $rebillDays,
            $rebillAmount,
            $transactionId,
            $tax,
            $subscriptionId,
            $isNsf
        );
    }

    /**
     * Sets the charged taxes for the cross sale
     */
    private function setChargedTaxes(): void
    {
        $this->chargedAmountBeforeTaxes = $this->initialAmount;
        $this->chargedAmountAfterTaxes  = $this->initialAmount;
        $this->chargedTaxAmount         = null;

        if (!empty($this->tax)) {
            $this->chargedAmountBeforeTaxes = $this->tax['initialAmount']['beforeTaxes'];
            $this->chargedAmountAfterTaxes  = $this->tax['initialAmount']['afterTaxes'];
            $this->chargedTaxAmount         = $this->tax['initialAmount']['taxes'];
        }
    }
}
