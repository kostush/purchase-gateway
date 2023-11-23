<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Exception;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\AttemptedTransactions;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Member;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\Payment;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\PaymentCC;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\PaymentCheck;
use ProBillerNG\PurchaseGateway\Application\BI\Processed\SelectedCrossSell;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CardInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;

class PurchaseProcessed extends PurchaseEvent
{
    public const TYPE             = 'Purchase_Processed';
    public const LATEST_VERSION   = 8;
    public const BANK_DECLINE     = 'bankdecline';

    /** @var string */
    protected $status;

    /** @var string */
    protected $purchaseId;

    /** @var string */
    protected $memberId;

    /** @var string */
    protected $itemId;

    /** @var string */
    protected $bundleId;

    /** @var string */
    protected $addonId;

    /** @var string */
    protected $subscriptionId;

    /** @var Processed\Member */
    protected $member;

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

    /** @var bool */
    protected $existingMember;

    /** @var  array|null */
    protected $atlasCode;

    /** @var array|null */
    protected $fraudRecommendation;

    /** @var array|null */
    protected $fraudRecommendationCollection;

    /** @var  string|null */
    protected $paymentMethod;

    /** @var  string|null */
    protected $trafficSource;

    /** @var bool */
    protected $threeDRequired;

    /** @var int|null */
    protected $threeDVersion;

    /** @var @var bool */
    protected $threeDFrictionless;

    /** @var bool */
    protected $isNsf;

    /** @var bool */
    protected $isThirdParty;

    /** @var AttemptedTransactions */
    private $attemptedTransactions;

    /** @var array|null */
    private $gatewayServiceFlags;

    /** @var array|null */
    private $blacklistedInfo;

    /** @var bool|null */
    private $threeDchallenged;

    /** @var float */
    private $chargedAmountBeforeTaxes;

    /** @var float */
    private $chargedAmountAfterTaxes;

    /** @var float|null */
    private $chargedTaxAmount;

    /**
     * PurchaseProcessed constructor.
     *
     * @param Processed\Member              $member                        Member Info
     * @param Processed\Payment|null        $payment                       Payment ex {"first6": "xxxxxx","last4": "xxxx"}
     * @param Processed\SelectedCrossSell[] $selectedCrossSells            Selected Cross Sells.
     * @param string                        $sessionId                     Session Id.
     * @param string                        $siteId                        Site Id.
     * @param string                        $itemId                        Item Id.
     * @param string                        $bundleId                      Bundle Id.
     * @param string                        $addonId                       Addon Id.
     * @param string                        $status                        Purchase Status
     * @param string                        $purchaseId                    Purchase Id.
     * @param string                        $memberId                      Member Id.
     * @param string                        $taxAmountInformed             Tax Amount Informed
     * @param int                           $initialDays                   Initial Days
     * @param float                         $initialAmount                 Initial Amount
     * @param bool                          $threeDRequired                Was 3DS required
     * @param int|null                      $threeDVersion                 ThreeD version.
     * @param bool|null                     $threeDFrictionless            ThreeD frictionless.
     * @param bool                          $isThirdParty                  Is third party biller
     * @param bool                          $isNsf                         Is Nsf
     * @param int|null                      $rebillDays                    Rebill Days
     * @param float|null                    $rebillAmount                  Rebill Amount
     * @param string|null                   $subscriptionId                Subscription Id
     * @param string|null                   $transactionId                 Transaction Id
     * @param array|null                    $tax                           Tax
     * @param string|null                   $entrySiteId                   Entry Site Id
     * @param array|null                    $paymentTemplate               Payment Template Array
     * @param bool                          $existingMember                Existing member
     * @param AttemptedTransactions         $attemptedTransactions         Attempted transactions
     * @param array|null                    $atlasCode                     Atlas Code Decoded
     * @param array|null                    $fraudRecommendation           Fraud recommendation
     * @param string|null                   $paymentMethod                 Payment method
     * @param string|null                   $trafficSource                 Traffic source
     * @param array|null                    $fraudRecommendationCollection Fraud Recommendation
     * @param array|null                    $gatewayServiceFlags           gateway service flag
     * @param array|null                    $blacklistedInfo               Blacklisted info
     * @param bool|null                     $threeDchallenged              Flag indicates if 3DS is challenged
     * @throws Exception
     */
    public function __construct(
        Processed\Member $member,
        ?Processed\Payment $payment,
        array $selectedCrossSells,
        string $sessionId,
        string $siteId,
        string $itemId,
        string $bundleId,
        string $addonId,
        string $status,
        string $purchaseId,
        string $memberId,
        string $taxAmountInformed,
        int $initialDays,
        float $initialAmount,
        bool $threeDRequired,
        ?int $threeDVersion,
        ?bool $threeDFrictionless,
        bool $isThirdParty,
        bool $isNsf,
        ?int $rebillDays,
        ?float $rebillAmount,
        ?string $subscriptionId,
        ?string $transactionId,
        ?array $tax,
        ?string $entrySiteId,
        ?array $paymentTemplate,
        bool $existingMember,
        AttemptedTransactions $attemptedTransactions,
        ?array $atlasCode,
        ?array $fraudRecommendation,
        ?string $paymentMethod,
        ?string $trafficSource,
        ?array $fraudRecommendationCollection,
        ?array $gatewayServiceFlags,
        ?array $blacklistedInfo,
        ?bool $threeDchallenged = null
    ) {
        parent::__construct(self::TYPE, $sessionId, $siteId, new \DateTimeImmutable());

        $this->itemId                        = $itemId;
        $this->bundleId                      = $bundleId;
        $this->addonId                       = $addonId;
        $this->member                        = $member;
        $this->payment                       = $payment;
        $this->selectedCrossSells            = $selectedCrossSells;
        $this->status                        = $status;
        $this->purchaseId                    = $purchaseId;
        $this->memberId                      = $memberId;
        $this->taxAmountInformed             = $taxAmountInformed;
        $this->initialDays                   = $initialDays;
        $this->initialAmount                 = $initialAmount;
        $this->threeDRequired                = $threeDRequired;
        $this->threeDVersion                 = $threeDVersion;
        $this->threeDFrictionless            = $threeDFrictionless;
        $this->rebillDays                    = $rebillDays;
        $this->rebillAmount                  = $rebillAmount;
        $this->subscriptionId                = $subscriptionId;
        $this->transactionId                 = $transactionId;
        $this->tax                           = $tax;
        $this->entrySiteId                   = $entrySiteId;
        $this->paymentTemplate               = $paymentTemplate;
        $this->existingMember                = $existingMember;
        $this->atlasCode                     = $atlasCode;
        $this->fraudRecommendation           = $fraudRecommendation;
        $this->fraudRecommendationCollection = $fraudRecommendationCollection;
        $this->paymentMethod                 = $paymentMethod;
        $this->trafficSource                 = $trafficSource;
        $this->attemptedTransactions         = $attemptedTransactions;
        $this->isThirdParty                  = $isThirdParty;
        $this->isNsf                         = $isNsf;
        $this->gatewayServiceFlags           = $gatewayServiceFlags;
        $this->blacklistedInfo               = $blacklistedInfo;
        $this->threeDchallenged              = $threeDchallenged;

        $this->setChargedTaxes();

        $this->setValue($this->toArray());
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param Payment|null    $payment         Payment data
     * @param array|null      $paymentTemplate Payment template
     *
     * @return PurchaseProcessed
     *
     * @throws Exception
     */
    protected static function create(
        PurchaseProcess $purchaseProcess,
        ?Payment $payment,
        ?array $paymentTemplate
    ): self {
        $crossSaleData       = [];
        $mainSubscriptionId  = null;
        $rebillDays          = null;
        $rebillAmount        = null;
        $threeDRequired      = false;
        $gatewayServiceFlags = [];
        $threeDVersion       = null;
        $threeDFrictionless  = null;

        // this is needed when we don't have a transaction (success = false)
        $purchaseId = (string) PurchaseId::create();
        $memberId   = (string) MemberId::create();

        foreach ($purchaseProcess->retrieveProcessedCrossSales() as $crossSaleInitializedItem) {
            $crossSaleData[] = self::createCrossSaleData($crossSaleInitializedItem);
        }

        if ($purchaseProcess->purchase() instanceof Purchase) {
            $mainPurchaseItem = $purchaseProcess->purchase()->retrieveMainPurchaseItem();
            if ($mainPurchaseItem instanceof ProcessedBundleItem) {
                $mainSubscriptionId = (string) $mainPurchaseItem
                    ->subscriptionInfo()
                    ->subscriptionId();
            }

            $purchaseId = (string) $purchaseProcess->purchase()->purchaseId();
            $memberId   = (string) $purchaseProcess->purchase()->memberId();
        }

        /** @var InitializedItem $mainInitializedItem */
        $mainInitializedItem = $purchaseProcess->retrieveMainPurchaseItem();

        $billerName = $purchaseProcess->cascade()->currentBiller()->name();

        /**
         * @var Biller
         */
        $biller = BillerFactoryService::create($billerName);

        $taxAmountInformed = 'No';
        $tax               = [];
        if ($mainInitializedItem->taxInformation() instanceof TaxInformation) {
            $taxAmountInformed = 'Yes';

            $tax = array_merge(
                $mainInitializedItem->chargeInformation()->fullTaxBreakDownArray(),
                $mainInitializedItem->taxInformation()->toArray()
            );
        }

        if ($purchaseProcess->fraudAdvice()) {
            $threeDRequired = $purchaseProcess->fraudAdvice()->isForceThreeD();
        }

        $mainTransactionState = Transaction::STATUS_ABORTED;
        $mainTransactionId    = null;

        if ($mainInitializedItem->transactionCollection()->count() > 0) {
            /**
             * @var Transaction $mainTransaction
             */
            $mainTransaction      = $mainInitializedItem->transactionCollection()->last();
            $mainTransactionState = $mainTransaction->state();
            $mainTransactionId    = (string) $mainTransaction->transactionId();
            $threeDVersion        = $mainTransaction->threeDVersion();
            $threeDFrictionless   = $threeDRequired ? $mainTransaction->threeDFrictionless() : null;
        }

        if ($mainInitializedItem->chargeInformation() instanceof BundleRebillChargeInformation) {
            $rebillDays   = $mainInitializedItem->chargeInformation()->repeatEvery()->days();
            $rebillAmount = $mainInitializedItem->chargeInformation()->rebillAmount()->value();
        }

        if ($purchaseProcess->fraudAdvice()) {
            $threeDRequired = $purchaseProcess->fraudAdvice()->isForceThreeD();
        }

        if ($purchaseProcess->cascade() && $purchaseProcess->cascade()->removedBillersFor3DS()->count() > 0) {
            $gatewayServiceFlags['overrideCascadeNetbillingReason3ds'] = $purchaseProcess->cascade()
                ->removedBillersFor3DS()
                ->contains(NetbillingBiller::BILLER_NAME);
        } else {
            $gatewayServiceFlags['overrideCascadeNetbillingReason3ds'] = false;
        }

        // For every 3DS transaction setup the value of $threeDchallenged
        //      false: if the transaction was frictionless
        //      true:  if the transaction was challenged (user was required to put his info)
        if ($threeDRequired) {
            $threeDchallenged = empty($threeDFrictionless);
        }

        return new static(
            Member::create(
                (string) $purchaseProcess->userInfo()->email(),
                (string) $purchaseProcess->userInfo()->username(),
                (string) $purchaseProcess->userInfo()->firstName(),
                (string) $purchaseProcess->userInfo()->lastName(),
                (string) $purchaseProcess->userInfo()->countryCode(),
                (string) $purchaseProcess->userInfo()->zipCode(),
                $purchaseProcess->userInfo()->address(),
                $purchaseProcess->userInfo()->city(),
                (string) $purchaseProcess->userInfo()->phoneNumber()
            ),
            $payment,
            $crossSaleData,
            (string) $purchaseProcess->sessionId(),
            (string) $mainInitializedItem->siteId(),
            (string) $mainInitializedItem->itemId(),
            (string) $mainInitializedItem->bundleId(),
            (string) $mainInitializedItem->addonId(),
            $mainTransactionState,
            $purchaseId,
            $memberId,
            $taxAmountInformed,
            $mainInitializedItem->chargeInformation()->validFor()->days(),
            $mainInitializedItem->chargeInformation()->initialAmount()->value(),
            $threeDRequired,
            $threeDVersion,
            $threeDFrictionless,
            $biller->isThirdParty(),
            $mainInitializedItem->wasItemNsfPurchase(),
            $rebillDays,
            $rebillAmount,
            $mainSubscriptionId,
            $mainTransactionId,
            $tax,
            $purchaseProcess->entrySiteId(),
            $paymentTemplate,
            $purchaseProcess->isExistingMemberPurchase(),
            AttemptedTransactions::create(
                $purchaseProcess->gatewaySubmitNumber(),
                $billerName,
                $mainTransactionState,
                $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->toArray(),
                $purchaseProcess->retrieveProcessedCrossSales(),
                $paymentTemplate
            ),
            $purchaseProcess->atlasFields()->atlasCodeDecoded(),     // BG-37030 send to BI Event
            $purchaseProcess->fraudRecommendation() ? $purchaseProcess->fraudRecommendation()->toArray() : null,
            $purchaseProcess->paymentMethod(),
            $purchaseProcess->trafficSource(),
            $purchaseProcess->fraudRecommendationCollection() ? $purchaseProcess->fraudRecommendationCollection()
                ->toArray() : null,
            $gatewayServiceFlags,
            self::createBlacklistedInfo($purchaseProcess),
            isset($threeDchallenged) ? $threeDchallenged : null
        );
    }

    /**
     * @param InitializedItem $crossSaleInitializedItem Initialized item
     *
     * @return SelectedCrossSell
     */
    protected static function createCrossSaleData(InitializedItem $crossSaleInitializedItem): SelectedCrossSell
    {
        $transactionId     = null;
        $crossSaleData     = null;
        $subscriptionId    = null;
        $rebillDays        = null;
        $rebillAmount      = null;
        $crossSaleTaxArray = [];
        $transactionStatus = Transaction::STATUS_ABORTED;

        if ($crossSaleInitializedItem->taxInformation() instanceof TaxInformation) {
            $crossSaleTaxArray = array_merge(
                $crossSaleInitializedItem->chargeInformation()->fullTaxBreakDownArray(),
                $crossSaleInitializedItem->taxInformation()->toArray()
            );
        }

        $subscriptionId = $crossSaleInitializedItem->subscriptionId();

        if ($crossSaleInitializedItem->transactionCollection()->count() > 0) {
            $transactionStatus = $crossSaleInitializedItem->transactionCollection()->last()->state();
            $transactionId     = (string) $crossSaleInitializedItem->transactionCollection()->last()->transactionId();
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
            $crossSaleInitializedItem->wasItemNsfPurchase()
        );

        return $crossSaleData;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     *
     * @return PurchaseProcessed
     *
     * @throws LoggerException
     * @throws ValidationException
     * @throws Exception
     */
    public static function createForNewCC(
        PurchaseProcess $purchaseProcess
    ): self {
        /** @var NewCCPaymentInfo|CardInfo $paymentInfo */
        $paymentInfo = $purchaseProcess->paymentInfo();
        $payment     = null;

        switch (get_class($paymentInfo)) {
            case CardInfo::class:
                $payment = PaymentCC::create(
                    (string) Bin::createFromCCNumber($paymentInfo->first6()),
                    (string) LastFour::createFromCCNumber($paymentInfo->last4()),
                    $paymentInfo->expirationMonth(),
                    $paymentInfo->expirationYear()
                );

                break;
            case NewCCPaymentInfo::class:
                $payment = PaymentCC::create(
                    (string) Bin::createFromCCNumber($paymentInfo->ccNumber()),
                    (string) LastFour::createFromCCNumber($paymentInfo->ccNumber()),
                    $paymentInfo->expirationMonth(),
                    $paymentInfo->expirationYear()
                );

                break;
            default:
                break;
        }

        return self::create(
            $purchaseProcess,
            $payment,
            null
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     *
     * @return PurchaseProcessed
     * @throws Exception
     */
    public static function createForCheck(
        PurchaseProcess $purchaseProcess
    ): self {
        $paymentInfo = $purchaseProcess->paymentInfo();
        $payment     = null;

        if (!$paymentInfo instanceof ChequePaymentInfo) {
            throw new \Exception('Wrong payment information given');
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
     * @param PurchaseProcess $purchaseProcess Purchase process
     *
     * @return PurchaseProcessed
     *
     * @throws LoggerException
     * @throws ValidationException
     * @throws Exception
     */
    public static function createForPaymentTemplate(
        PurchaseProcess $purchaseProcess
    ): self {
        $paymentTemplate = $purchaseProcess->retrieveSelectedPaymentTemplate();
        $payment         = PaymentCC::create('', '', '', '');
        $billerFields    = [];

        if ($paymentTemplate instanceof PaymentTemplate) {
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
            $payment,
            $billerFields
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process.
     * @return array|null
     */
    public static function createBlacklistedInfo(PurchaseProcess $purchaseProcess): ?array
    {
        if (!$purchaseProcess->creditCardWasBlacklisted()) {
            return null;
        }

        $bankDeclineCode = null;
        $mainTransaction = $purchaseProcess->retrieveMainPurchaseItem()->lastTransaction();

        if ($mainTransaction instanceof Transaction
            && $mainTransaction->errorClassificationHasHardType()
        ) {
            $bankDeclineCode = $mainTransaction->errorClassification()->groupDecline();
        }

        if ($bankDeclineCode === null) {
            /** @var InitializedItem $crossSale */
            foreach ($purchaseProcess->retrieveProcessedCrossSales() as $crossSale) {
                $transaction = $crossSale->lastTransaction();

                if ($transaction instanceof Transaction
                    && $transaction->errorClassificationHasHardType()
                ) {
                    $bankDeclineCode = $transaction->errorClassification()->groupDecline();
                    break;
                }
            }
        }

        return [
            'reason'          => self::BANK_DECLINE,
            'bankdeclinecode' => $bankDeclineCode
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $selectedCrossSellsList = [];
        if (is_array($this->selectedCrossSells)) {
            /** @var SelectedCrossSell $selectedCrossSell */
            foreach ($this->selectedCrossSells as $selectedCrossSell) {
                $selectedCrossSellsList[] = $selectedCrossSell->toArray();
            }
        }

        return [
            'type'                          => self::TYPE,
            'version'                       => $this->version,
            'timestamp'                     => $this->timestamp,
            'sessionId'                     => $this->sessionId,
            'siteId'                        => $this->siteId,
            'itemId'                        => $this->itemId,
            'bundleId'                      => $this->bundleId,
            'addonId'                       => $this->addonId,
            'status'                        => $this->status,
            'purchaseId'                    => $this->purchaseId,
            'memberId'                      => $this->memberId,
            'subscriptionId'                => $this->subscriptionId,
            'memberInfo'                    => $this->member->toArray(),
            'existingMember'                => $this->existingMember,
            'entrySiteId'                   => $this->entrySiteId,
            'selectedCrossSells'            => $selectedCrossSellsList,
            'payment'                       => $this->payment ? $this->payment->toArray() : null,
            'paymentTemplate'               => $this->paymentTemplate,
            'taxAmountInformed'             => $this->taxAmountInformed,
            'transactionId'                 => $this->transactionId,
            'initialAmount'                 => $this->initialAmount,
            'initialDays'                   => $this->initialDays,
            'rebillAmount'                  => $this->rebillAmount,
            'rebillDays'                    => $this->rebillDays,
            'tax'                           => $this->tax,
            'chargedAmountBeforeTaxes'      => $this->chargedAmountBeforeTaxes,
            'chargedAmountAfterTaxes'       => $this->chargedAmountAfterTaxes,
            'chargedTaxAmount'              => $this->chargedTaxAmount,
            'atlasCode'                     => $this->atlasCode,
            'fraudRecommendation'           => $this->fraudRecommendation,
            'fraudRecommendationCollection' => $this->fraudRecommendationCollection,
            'paymentMethod'                 => $this->paymentMethod,
            'trafficSource'                 => $this->trafficSource,
            'cascadeAttemptedTransactions'  => [$this->attemptedTransactions->toArray()],
            'threeDRequired'                => $this->threeDRequired,
            'threeDVersion'                 => $this->threeDVersion,
            'threeDFrictionless'            => $this->threeDFrictionless,
            'threeDchallenged'              => $this->threeDchallenged,
            'isThirdParty'                  => $this->isThirdParty,
            'isNsf'                         => $this->isNsf,
            'blacklistedInfo'               => $this->blacklistedInfo,
            'gatewayServiceFlags'           => $this->gatewayServiceFlags,
        ];
    }

    /**
     * Sets the charged taxes for the purchase
     */
    private function setChargedTaxes(): void
    {
        // In case there is no tax set, both have to be equal to the initialAmount
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
