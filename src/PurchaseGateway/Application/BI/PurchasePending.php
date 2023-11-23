<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use DateTimeImmutable;
use Exception;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Payment;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\PaymentCC;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\PaymentCheck;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\SelectedCrossSell;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class PurchasePending extends PurchaseEvent
{
    const TYPE = 'Purchase_Pending';

    const LATEST_VERSION = 1;

    /** @var string */
    protected $status;

    /** @var string */
    protected $itemId;

    /** @var string */
    protected $bundleId;

    /** @var string */
    protected $addonId;

    /** @var Processed\SelectedCrossSell[] */
    protected $selectedCrossSells;

    /** @var Processed\Payment */
    protected $payment;

    /** @var int */
    protected $initialDays;

    /** @var float */
    protected $initialAmount;

    /** @var int|null */
    protected $rebillDays;

    /** @var float|null */
    protected $rebillAmount;

    /** @var string */
    protected $taxAmountInformed;

    /** @var string|null */
    protected $transactionId;

    /** @var array|null $tax */
    protected $tax;

    /** @var string|null */
    protected $entrySiteId;

    /** @var array|null */
    protected $paymentTemplate;

    /** @var  array|null */
    protected $trafficSource;

    /**
     * @var array|null
     */
    protected $fraudRecommendation;

    /**
     * @var array|null
     */
    protected $fraudRecommendationCollection;

    /** @var bool */
    protected $threeDRequired;

    /**
     * PurchasePending constructor.
     * @param Processed\Payment             $payment                       Payment ex {"first6":"xxxxxx","last4":"xxxx"}
     * @param Processed\SelectedCrossSell[] $selectedCrossSells            Selected Cross Sells.
     * @param string                        $sessionId                     Session Id.
     * @param string                        $siteId                        Site Id.
     * @param string                        $itemId                        Item Id.
     * @param string                        $bundleId                      Bundle Id.
     * @param string                        $addonId                       Addon Id.
     * @param string                        $status                        Purchase Status
     * @param string                        $taxAmountInformed             Tax Amount Informed
     * @param bool                          $threeDRequired                3DS required
     * @param int                           $initialDays                   Initial Days
     * @param float                         $initialAmount                 Initial Amount
     * @param int|null                      $rebillDays                    Rebill Days
     * @param float|null                    $rebillAmount                  Rebill Amount
     * @param string|null                   $transactionId                 Transaction Id
     * @param array|null                    $tax                           Tax
     * @param string                        $entrySiteId                   Entry Site Id
     * @param array|null                    $paymentTemplate               Payment Template Array
     * @param array|null                    $trafficSource                 Atlas Code Decoded
     * @param array|null                    $fraudRecommendation           Fraud Recommendation
     * @param array|null                    $fraudRecommendationCollection Fraud recommendation collection.
     * @throws Exception
     */
    public function __construct(
        Processed\Payment $payment,
        array $selectedCrossSells,
        string $sessionId,
        string $siteId,
        string $itemId,
        string $bundleId,
        string $addonId,
        string $status,
        string $taxAmountInformed,
        bool $threeDRequired,
        int $initialDays,
        float $initialAmount,
        ?int $rebillDays,
        ?float $rebillAmount,
        ?string $transactionId,
        ?array $tax,
        ?string $entrySiteId,
        ?array $paymentTemplate,
        ?array $trafficSource,
        ?array $fraudRecommendation,
        ?array $fraudRecommendationCollection
    ) {
        parent::__construct(self::TYPE, $sessionId, $siteId, new DateTimeImmutable());

        $this->itemId              = $itemId;
        $this->bundleId            = $bundleId;
        $this->addonId             = $addonId;
        $this->payment             = $payment;
        $this->selectedCrossSells  = $selectedCrossSells;
        $this->status              = $status;
        $this->taxAmountInformed   = $taxAmountInformed;
        $this->threeDRequired      = $threeDRequired;
        $this->initialDays         = $initialDays;
        $this->initialAmount       = $initialAmount;
        $this->rebillDays          = $rebillDays;
        $this->rebillAmount        = $rebillAmount;
        $this->transactionId       = $transactionId;
        $this->tax                 = $tax;
        $this->entrySiteId         = $entrySiteId;
        $this->paymentTemplate     = $paymentTemplate;
        $this->trafficSource       = $trafficSource;
        $this->fraudRecommendation = $fraudRecommendation;

        $this->fraudRecommendationCollection = $fraudRecommendationCollection;

        $this->setValue($this->toArray());
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param Payment         $payment         Payment data
     * @param array|null      $paymentTemplate Payment template
     *
     * @return PurchasePending
     *
     * @throws Exception
     */
    protected static function create(
        PurchaseProcess $purchaseProcess,
        Payment $payment,
        ?array $paymentTemplate
    ): self {
        $crossSaleData  = [];
        $rebillDays     = null;
        $rebillAmount   = null;
        $threeDRequired = false;


        foreach ($purchaseProcess->retrieveProcessedCrossSales() as $crossSaleInitializedItem) {
            $crossSaleData[] = self::createCrossSaleData($crossSaleInitializedItem);
        }

        /** @var InitializedItem $mainInitializedItem */
        $mainInitializedItem = $purchaseProcess->retrieveMainPurchaseItem();

        $taxAmountInformed = 'No';
        $tax               = [];
        if ($mainInitializedItem->taxInformation() instanceof TaxInformation) {
            $taxAmountInformed = 'Yes';

            $tax = array_merge(
                $mainInitializedItem->chargeInformation()->fullTaxBreakDownArray(),
                $mainInitializedItem->taxInformation()->toArray()
            );
        }

        $mainTransactionState = Transaction::STATUS_ABORTED;
        $mainTransactionId    = null;
        if ($mainInitializedItem->transactionCollection()->count() > 0) {
            $mainTransactionState = $mainInitializedItem->transactionCollection()->last()->state();
            $mainTransactionId    = (string) $mainInitializedItem->transactionCollection()->last()->transactionId();
        }

        if ($mainInitializedItem->chargeInformation() instanceof BundleRebillChargeInformation) {
            $rebillDays   = $mainInitializedItem->chargeInformation()->repeatEvery()->days();
            $rebillAmount = $mainInitializedItem->chargeInformation()->rebillAmount()->value();
        }

        if ($purchaseProcess->fraudAdvice() instanceof FraudAdvice) {
            $threeDRequired = $purchaseProcess->fraudAdvice()->isForceThreeD();
        }

        $fraudCollection = $purchaseProcess->fraudRecommendationCollection() ? $purchaseProcess
            ->fraudRecommendationCollection()->toArray() : null;

        return new static(
            $payment,
            $crossSaleData,
            (string) $purchaseProcess->sessionId(),
            (string) $mainInitializedItem->siteId(),
            (string) $mainInitializedItem->itemId(),
            (string) $mainInitializedItem->bundleId(),
            (string) $mainInitializedItem->addonId(),
            $mainTransactionState,
            $taxAmountInformed,
            $threeDRequired,
            $mainInitializedItem->chargeInformation()->validFor()->days(),
            $mainInitializedItem->chargeInformation()->initialAmount()->value(),
            $rebillDays,
            $rebillAmount,
            $mainTransactionId,
            $tax,
            $purchaseProcess->entrySiteId(),
            $paymentTemplate,
            $purchaseProcess->atlasFields()->atlasCodeDecoded(),     // BG-37030 send to BI Event
            $purchaseProcess->fraudRecommendation() ? $purchaseProcess->fraudRecommendation()->toArray() : null,
            $fraudCollection
        );
    }

    /**
     * @param InitializedItem $crossSaleInitializedItem Initialized item
     * @return SelectedCrossSell
     */
    protected static function createCrossSaleData(InitializedItem $crossSaleInitializedItem): SelectedCrossSell
    {
        $crossSaleData     = null;
        $rebillDays        = null;
        $rebillAmount      = null;
        $crossSaleTaxArray = [];

        $transactionStatus = $crossSaleInitializedItem->lastTransactionState() ?? Transaction::STATUS_ABORTED;
        $transactionId     = (string) $crossSaleInitializedItem->lastTransactionId();
        $subscriptionId    = $crossSaleInitializedItem->subscriptionId();

        if ($crossSaleInitializedItem->taxInformation() instanceof TaxInformation) {
            $crossSaleTaxArray = array_merge(
                $crossSaleInitializedItem->chargeInformation()->fullTaxBreakDownArray(),
                $crossSaleInitializedItem->taxInformation()->toArray()
            );
        }

        if ($crossSaleInitializedItem->chargeInformation() instanceof BundleRebillChargeInformation) {
            $rebillDays   = $crossSaleInitializedItem->chargeInformation()->repeatEvery()->days();
            $rebillAmount = $crossSaleInitializedItem->chargeInformation()->rebillAmount()->value();
        }

        $crossSaleData = SelectedCrossSell::create(
            $transactionStatus,
            (string) $crossSaleInitializedItem->siteId(),
            (string) $crossSaleInitializedItem->itemId(),
            (string) $crossSaleInitializedItem->bundleId(),
            (string) $crossSaleInitializedItem->addonId(),
            $crossSaleInitializedItem->chargeInformation()->validFor()->days(),
            $crossSaleInitializedItem->chargeInformation()->initialAmount()->value(),
            $rebillDays,
            $rebillAmount,
            $transactionId,
            $crossSaleTaxArray,
            $subscriptionId,
            null
        );

        return $crossSaleData;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     *
     * @return PurchasePending
     *
     * @throws \ProBillerNG\Logger\Exception
     * @throws ValidationException
     * @throws Exception
     */
    public static function createForNewCC(PurchaseProcess $purchaseProcess): self
    {
        /** @var NewCCPaymentInfo $paymentInfo */
        $paymentInfo = $purchaseProcess->paymentInfo();

        $payment = PaymentCC::create(
            (string) Bin::createFromCCNumber($paymentInfo->ccNumber()),
            (string) LastFour::createFromCCNumber($paymentInfo->ccNumber()),
            $paymentInfo->expirationMonth(),
            $paymentInfo->expirationYear()
        );

        return self::create(
            $purchaseProcess,
            $payment,
            null
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     *
     * @return PurchasePending
     *
     * @throws \ProBillerNG\Logger\Exception
     * @throws ValidationException
     * @throws Exception
     */
    public static function createForPaymentTemplate(PurchaseProcess $purchaseProcess): self
    {
        $paymentTemplate = $purchaseProcess->retrieveSelectedPaymentTemplate();

        $billerFields = [];

        if ($paymentTemplate !== null) {
            $payment = PaymentCC::create(
                (string) Bin::createFromString(
                    $paymentTemplate->firstSix()
                ),
                (string) LastFour::createFromString(
                    $paymentTemplate->lastFour()
                ),
                $paymentTemplate->expirationMonth(),
                $paymentTemplate->expirationYear()
            );

            $billerFields = $paymentTemplate->billerFields();
        }

        return self::create(
            $purchaseProcess,
            $payment ?? PaymentCC::create('', '', '', ''),
            $billerFields
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     *
     * @return PurchasePending
     * @throws Exception
     */
    public static function createForCheck(
        PurchaseProcess $purchaseProcess
    ): self {
        $paymentInfo = $purchaseProcess->paymentInfo();
        $payment     = null;

        if (!$paymentInfo instanceof ChequePaymentInfo) {
            throw new Exception('Wrong payment information given');
        }

        $payment = PaymentCheck::create(
            $paymentInfo->routingNumber(),
            $paymentInfo->accountNumber(),
            $paymentInfo->socialSecurityLast4()
        );

        return self::create(
            $purchaseProcess,
            $payment,
            null
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $selectedCrossSellsList = [];

        /** @var SelectedCrossSell $selectedCrossSell */
        foreach ($this->selectedCrossSells as $selectedCrossSell) {
            $selectedCrossSellsList[] = $selectedCrossSell->toArray();
        }

        return [
            'type'                => self::TYPE,
            'version'             => $this->version,
            'timestamp'           => $this->timestamp,
            'sessionId'           => $this->sessionId,
            'siteId'              => $this->siteId,
            'itemId'              => $this->itemId,
            'bundleId'            => $this->bundleId,
            'addonId'             => $this->addonId,
            'status'              => $this->status,
            'entrySiteId'         => $this->entrySiteId,
            'selectedCrossSells'  => $selectedCrossSellsList,
            'payment'             => $this->payment->toArray(),
            'paymentTemplate'     => $this->paymentTemplate,
            'taxAmountInformed'   => $this->taxAmountInformed,
            'transactionId'       => $this->transactionId,
            'initialAmount'       => $this->initialAmount,
            'initialDays'         => $this->initialDays,
            'rebillAmount'        => $this->rebillAmount,
            'rebillDays'          => $this->rebillDays,
            'tax'                 => $this->tax,
            'trafficSource'       => $this->trafficSource,
            'fraudRecommendation' => $this->fraudRecommendation,
            'fraudRecommendationCollection' => $this->fraudRecommendationCollection,
            'threeDRequired'      => $this->threeDRequired,
        ];
    }
}
