<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Event;

use DateTimeImmutable;
use JMS\Serializer\SerializerBuilder;
use Probiller\Common\Enums\BusinessTransactionOperation\BusinessTransactionOperation;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;

class PurchaseProcessed extends BaseEvent
{
    const LATEST_VERSION = 14;

    /**
     * @var string
     */
    protected $purchaseId;

    /**
     * @var array
     */
    protected $transactionCollection;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $memberId;

    /**
     * @var string
     */
    protected $subscriptionId;

    /**
     * @var string|null
     */
    protected $entrySiteId;

    /**
     * @var array|null
     */
    protected $memberInfo;

    /**
     * @var array
     */
    protected $crossSalePurchaseData;

    /**
     * @var array
     */
    protected $payment;

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
     * @var string|null
     */
    protected $subscriptionUsername;

    /**
     * @var string|null
     */
    protected $subscriptionPassword;

    /**
     * @var int|null
     */
    protected $rebillFrequency;

    /**
     * @var int|null
     */
    protected $initialDays;

    /**
     * @var string|null
     */
    protected $atlasCode;

    /**
     * @var string|null
     */
    protected $atlasData;

    /**
     * @var string|null
     */
    protected $ipAddress;

    /**
     * @var bool
     */
    protected $isTrial;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var float|null
     */
    protected $rebillAmount;

    /**
     * @var array|null
     */
    protected $amounts;

    /**
     * @var bool
     */
    protected $isExistingMember;

    /**
     * @var bool
     */
    protected $threeDRequired;

    /**
     * @var bool
     */
    protected $isThirdParty;

    /** @var bool */
    protected $isNsf;

    /**
     * @var string|null
     */
    private $paymentMethod;

    /**
     * @var string|null
     */
    private $trafficSource;

    /**
     * @var int|null
     */
    private $threedVersion;

    /**
     * @var bool
     */
    private $threedFrictionless;

    /**
     * This is used for main purchase not for cross-sale
     * @var bool
     */
    private $isUsernamePadded;

    /**
     * This is used to mark an event if it was imported by api mode to Legacy
     * @var bool
     */
    private $isImportedByApi;

    /**
     * @var bool
     */
    private $skipVoidTransaction;

    /**
     * PurchaseProcessed constructor.
     *
     * @param string      $purchaseId            Purchase id
     * @param array       $transactionCollection Transaction id
     * @param string      $sessionId             Session id
     * @param string      $siteId                Site id
     * @param string      $status                Status
     * @param string      $memberId              Member id
     * @param string      $subscriptionId        Subscription id
     * @param array|null  $memberInfo            Member info
     * @param array       $crossSalePurchaseData Cross sale purchase data
     * @param array       $payment               Payment
     * @param string      $itemId                Item id
     * @param string      $bundleId              Bundle id
     * @param string      $addOnId               AddOn Id
     * @param bool        $threeDRequired        Was 3DS required
     * @param int|null    $threeDVersion         3DS version
     * @param bool        $threeDFrictionless    3DS version 2.0 frictionless
     * @param bool        $isThirdParty          Is third party biller
     * @param bool        $isNsf                 Is Nsf
     * @param int|null    $rebillFrequency       Rebill Amount Frequency
     * @param int|null    $initialDays           Purchase validity
     * @param bool        $isTrial               Is Trial product
     * @param float       $amount                Amount
     * @param float|null  $rebillAmount          Rebill Amount
     * @param string|null $atlasCode             Atlas Code
     * @param string|null $atlasData             Atlas Data
     * @param string|null $ipAddress             Ip address
     * @param array|null  $tax                   Tax amounts
     * @param bool        $isExistingMember      Existing User
     * @param string|null $entrySiteId           Entry site id
     * @param string|null $paymentMethod         Payment method
     * @param string|null $trafficSource         Traffic source
     * @param bool        $skipVoidTransaction   Skip void transaction
     * @param bool        $isUsernamePadded      Username padded
     * @param bool        $isImportedByApi       Is imported by api
     * @throws \Exception
     */
    public function __construct(
        string $purchaseId,
        array $transactionCollection,
        string $sessionId,
        string $siteId,
        string $status,
        string $memberId,
        string $subscriptionId,
        ?array $memberInfo,
        array $crossSalePurchaseData,
        array $payment,
        string $itemId,
        string $bundleId,
        string $addOnId,
        bool $threeDRequired,
        ?int $threeDVersion,
        bool $threeDFrictionless,
        bool $isThirdParty,
        bool $isNsf,
        ?int $rebillFrequency,
        ?int $initialDays,
        bool $isTrial,
        float $amount,
        ?float $rebillAmount,
        ?string $atlasCode,
        ?string $atlasData,
        ?string $ipAddress,
        ?array $tax,
        ?bool $isExistingMember,
        ?string $entrySiteId,
        ?string $paymentMethod,
        ?string $trafficSource,
        bool $skipVoidTransaction = false,
        bool $isUsernamePadded = false,
        bool $isImportedByApi = false
    ) {
        parent::__construct($purchaseId, new DateTimeImmutable());

        $memberInfo['password'] = $this->encryptPassword($memberInfo['password']);

        $this->purchaseId            = $purchaseId;
        $this->transactionCollection = $transactionCollection;
        $this->sessionId             = $sessionId;
        $this->siteId                = $siteId;
        $this->status                = $status;
        $this->memberId              = $memberId;
        $this->subscriptionId        = $subscriptionId;
        $this->memberInfo            = $memberInfo;
        $this->crossSalePurchaseData = $crossSalePurchaseData;
        $this->payment               = $payment;
        $this->itemId                = $itemId;
        $this->bundleId              = $bundleId;
        $this->addOnId               = $addOnId;
        $this->threeDRequired        = (bool) $threeDRequired;
        $this->threedVersion         = $threeDVersion;
        $this->threedFrictionless    = $threeDFrictionless;
        $this->isThirdParty          = (bool) $isThirdParty;
        $this->subscriptionUsername  = $memberInfo['username'] ?? null;
        $this->subscriptionPassword  = $memberInfo['password'] ?? null;
        $this->rebillFrequency       = $rebillFrequency;
        $this->initialDays           = $initialDays;
        $this->isTrial               = $isTrial;
        $this->amount                = $amount;
        $this->rebillAmount          = $rebillAmount;
        $this->atlasCode             = $atlasCode;
        $this->atlasData             = $atlasData;
        $this->ipAddress             = $ipAddress;
        $this->amounts               = $tax;
        $this->entrySiteId           = $entrySiteId;
        $this->isExistingMember      = (bool) $isExistingMember;
        $this->paymentMethod         = $paymentMethod;
        $this->trafficSource         = $trafficSource;
        $this->isNsf                 = $isNsf;
        $this->isUsernamePadded      = (bool) $isUsernamePadded;
        $this->isImportedByApi       = (bool) $isImportedByApi;
        $this->skipVoidTransaction   = $skipVoidTransaction;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param Purchase        $purchase        Purchase
     * @return PurchaseProcessed
     * @throws \Exception
     */
    public static function create(
        PurchaseProcess $purchaseProcess,
        Purchase $purchase
    ): self {
        $threeDRequired          = false;
        $initializedMainPurchase = $purchaseProcess->retrieveMainPurchaseItem()->toArray();

        $initializedCrossSaleData = self::buildInitializedCrossSaleData($purchaseProcess, $purchase);

        $status    = $purchaseProcess->wasMainItemPurchaseSuccessful();
        $userInfo  = $purchaseProcess->userInfo()->toArray();
        $atlasData = $purchaseProcess->atlasFields()->toArray();

        if ($purchaseProcess->fraudAdvice()) {
            $threeDRequired = $purchaseProcess->fraudAdvice()->isForceThreeD();
        }

        /**
         * @var Transaction $transaction
         */
        $transaction        = $purchaseProcess->retrieveMainPurchaseItem()->lastTransaction();
        $threeDVersion      = $transaction->threeDVersion();
        $threeDFrictionless = $transaction->threeDFrictionless();

        /**
         * @var Biller
         */
        $biller = BillerFactoryService::create($transaction->billerName());

        /** @var InitializedItem $mainInitializedItem */
        $mainInitializedItem = $purchaseProcess->retrieveMainPurchaseItem();

        return new self(
            (string) $purchase->purchaseId(),
            $initializedMainPurchase['transactionCollection'],
            (string) $purchase->sessionId(),
            $initializedMainPurchase['siteId'],
            $status ? Purchase::STATUS_SUCCESS : Purchase::STATUS_FAILED,
            (string) $purchase->memberId(),
            (string) $purchaseProcess->retrieveMainPurchaseItem()->subscriptionId(),
            $userInfo,
            $initializedCrossSaleData,
            $purchaseProcess->paymentInfo()->toArray(),
            (string) $purchaseProcess->retrieveMainPurchaseItem()->itemId(),
            $initializedMainPurchase['bundleId'],
            $initializedMainPurchase['addonId'],
            $threeDRequired,
            $threeDVersion,
            $threeDFrictionless,
            $biller->isThirdParty(),
            $mainInitializedItem->wasItemNsfPurchase(),
            $initializedMainPurchase['rebillDays'],
            $initializedMainPurchase['initialDays'],
            $initializedMainPurchase['isTrial'],
            $initializedMainPurchase['initialAmount'],
            $initializedMainPurchase['rebillAmount'],
            $atlasData['atlasCode'],
            $atlasData['atlasData'],
            $userInfo['ipAddress'],
            $initializedMainPurchase['tax'],
            $purchaseProcess->isExistingMemberPurchase(),
            $purchaseProcess->entrySiteId(),
            $purchaseProcess->paymentMethod(),
            $purchaseProcess->trafficSource(),
            $purchaseProcess->skipVoid()
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     * @param Purchase        $purchase        Purchase
     * @return array
     */
    private static function buildInitializedCrossSaleData(PurchaseProcess $purchaseProcess, Purchase $purchase): array
    {
        $initializedCrossSaleData = [];
        foreach ($purchaseProcess->retrieveProcessedCrossSales() as $crossSale) {

            /** @var InitializedItem $crossSale */
            $data = $crossSale->toArray();

            //TODO processedBundleItem should be a clone of initialized item with the subscription info added
            //All data necessary for the domain event should be picked up form the Purchase aggregate
            $processedCrossSale = $purchase->items()->offsetGet((string) $crossSale->itemId());
            $subscriptionId     = (string) $processedCrossSale->subscriptionInfo()->subscriptionId();

            $data['subscriptionId'] = $subscriptionId;

            // For each cross-sale we set false for isUsernamePadded at first
            $data['isUsernamePadded']                  = false;

            $initializedCrossSaleData[] = $data;
        }

        return $initializedCrossSaleData;
    }

    /**
     * @param InitializedItem $initializedItem
     * @param PurchaseProcess $purchaseProcess
     *
     * @return bool
     */
    private static function wasNfsPurchase(InitializedItem $initializedItem, PurchaseProcess $purchaseProcess): bool
    {
        $wasItemNsfPurchase = $initializedItem->wasItemNsfPurchase();
        // As we don't support the secrev with payment template with NFS we should not send the isNFS flag as true
        if ($initializedItem->wasItemNsfPurchase()
            && ($purchaseProcess->paymentInfo() instanceof ExistingPaymentInfo)) {
            $wasItemNsfPurchase = false;
        }

        return $wasItemNsfPurchase;
    }

    /**
     * @return string
     */
    public function purchaseId(): string
    {
        return $this->purchaseId;
    }

    /**
     * @return string
     */
    public function lastTransactionId(): string
    {
        //we only care about the last transaction, successful or otherwise
        return $this->lastTransaction()['transactionId'];
    }

    /**
     * @return array
     */
    public function lastTransaction(): array
    {
        //we only care about the last transaction, successful or otherwise
        return end($this->transactionCollection);
    }

    /**
     * @return array
     */
    public function transactionCollection(): array
    {
        return $this->transactionCollection;
    }

    /**
     * @param array $crossSalePurchaseData CrossSalePurchaseData
     * @return string
     */
    public function lastCrossSaleTransactionId(array $crossSalePurchaseData): string
    {
        //we only care about the last transaction, successful or otherwise
        return end($crossSalePurchaseData['transactionCollection'])['transactionId'];
    }

    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function siteId(): string
    {
        return $this->siteId;
    }

    /**
     * @return string
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function memberId(): string
    {
        return $this->memberId;
    }

    /**
     * @return string
     */
    public function subscriptionId(): string
    {
        return $this->subscriptionId;
    }

    /**
     * @return array|null
     */
    public function memberInfo(): ?array
    {
        return $this->memberInfo;
    }

    /**
     * @return array
     */
    public function crossSalePurchaseData(): array
    {
        return $this->crossSalePurchaseData;
    }

    /**
     * @return array
     */
    public function payment(): array
    {
        return $this->payment;
    }

    /**
     * @return string
     */
    public function itemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function bundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * @return string
     */
    public function addOnId(): string
    {
        return $this->addOnId;
    }

    /**
     * @return bool
     */
    public function threeDRequired(): bool
    {
        return (bool) $this->threeDRequired;
    }

    /**
     * @return int|null
     */
    public function threedVersion(): ?int
    {
        return $this->threedVersion;
    }

    /**
     * @return bool
     */
    public function threedFrictionless(): bool
    {
        return (bool) $this->threedFrictionless;
    }

    /**
     * @return bool
     */
    public function isThirdParty(): bool
    {
        return (bool) $this->isThirdParty;
    }

    /**
     * @return bool
     */
    public function isNsf(): bool
    {
        return (bool) $this->isNsf;
    }

    /**
     * @return bool
     */
    public function skipVoidTransaction(): bool
    {
        return $this->skipVoidTransaction;
    }

    /**
     * @return string|null
     */
    public function subscriptionUsername(): ?string
    {
        return $this->subscriptionUsername;
    }

    /**
     * @return string|null
     */
    public function subscriptionPassword(): ?string
    {
        return $this->subscriptionPassword;
    }

    /**
     * @return int|null
     */
    public function rebillFrequency(): ?int
    {
        return $this->rebillFrequency;
    }

    /**
     * @return int|null
     */
    public function initialDays(): ?int
    {
        return $this->initialDays;
    }

    /**
     * @return string|null
     */
    public function atlasCode(): ?string
    {
        return $this->atlasCode;
    }

    /**
     * @return string|null
     */
    public function atlasData(): ?string
    {
        return $this->atlasData;
    }

    /**
     * @return string|null
     */
    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->isTrial;
    }


    /**
     * @param string $password Plain password
     * @return string
     */
    private function encryptPassword(string $password): string
    {
        // Encoding to base64 since jms serializer only accepts UTF-8 charset
        return base64_encode(sodium_crypto_secretbox($password, env('ENCRYPTION_NONCE'), env('ENCRYPTION_KEY')));
    }

    /**
     * @return array|null
     */
    public function amounts(): ?array
    {
        return $this->amounts;
    }

    /**
     * @return float
     */
    public function amount(): float
    {
        return $this->amount;
    }

    /**
     * @return float|null
     */
    public function rebillAmount(): ?float
    {
        return $this->rebillAmount;
    }

    /**
     * @return string|null
     */
    public function entrySiteId(): ?string
    {
        return $this->entrySiteId;
    }

    /**
     * @return bool
     */
    public function isExistingMember(): bool
    {
        return (bool) $this->isExistingMember;
    }

    /**
     * @return string
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @return string
     */
    public function trafficSource(): ?string
    {
        return $this->trafficSource;
    }

    /**
     * @return bool
     */
    public function isUsernamePadded(): bool
    {
        return (bool) $this->isUsernamePadded;
    }

    /**
     * This is used to set username padded only for main purchase not for cross-sales
     * @return void
     */
    public function usernamePadded(): void
    {
        $this->isUsernamePadded = true;
    }

    /**
     * @return bool
     */
    public function isImportedByApi(): bool
    {
        return (bool) $this->isImportedByApi;
    }

    /**
     * @param bool $isImported
     *
     * @return bool
     */
    public function setIsImportedByApi(bool $isImported): bool
    {
        return $this->isImportedByApi = $isImported;
    }

    /**
     * @param string|null $paddedUsername Padded username
     *
     * @return string|null
     */
    public function setSubscriptionUsername(?string $paddedUsername): ?string
    {
        return $this->subscriptionUsername = $paddedUsername;
    }

    /**
     * @param array|null $memberInfo Member info
     *
     * @return array|null
     */
    public function setMemberInfo(?array $memberInfo): ? array
    {
        return $this->memberInfo = $memberInfo;
    }

    /**
     * We need this function because we are setting subscriptionUsername, subscriptionPassword
     * & usernamePadded flag for each cross-sale. As cross-sale data is array in purchase processed event
     * after setting values,we set back the cross-sale data into event. And this is only possible after legacy import
     * is successful through legacy api import endpoint and we get proper response.
     *
     * @param array $crossSaleData Cross sale data
     *
     * @return array
     */
    public function setCrossSalePurchaseData(array $crossSaleData): array
    {
        return $this->crossSalePurchaseData = $crossSaleData;
    }

    /**
     * @param string $json Json event data
     * @return PurchaseProcessed
     */
    public static function createFromJson(string $json): self
    {
        $serializer = SerializerBuilder::create()
            ->addDefaultHandlers()
            ->addMetadataDir(
                __DIR__ . DIRECTORY_SEPARATOR . '/Mapping'
            )
            ->build();
        return $serializer->deserialize($json, __CLASS__, 'json');
    }

    /**
     * @param array|null $crossSalePurchaseData
     *
     * @return int
     */
    public function getBusinessTransactionOperationType(?array $crossSalePurchaseData = null)
    {
        if (!empty($crossSalePurchaseData) && is_array($crossSalePurchaseData)) {
            if (isset($crossSalePurchaseData['initialDays']) && $crossSalePurchaseData['initialDays'] > 0) {
                return BusinessTransactionOperation::SUBSCRIPTIONPURCHASE;
            }

            if (isset($crossSalePurchaseData['initialDays']) && $crossSalePurchaseData['initialDays'] == 0) {
                return BusinessTransactionOperation::SINGLECHARGEPURCHASE;
            }
        } else {
            if (is_numeric($this->initialDays) && $this->initialDays > 0) {
                return BusinessTransactionOperation::SUBSCRIPTIONPURCHASE;
            }

            if (is_numeric($this->initialDays) && $this->initialDays == 0) {
                return BusinessTransactionOperation::SINGLECHARGEPURCHASE;
            }
        }

        return BusinessTransactionOperation::UNKNOWN;
    }

    /**
     * @param array|null $crossSalePurchaseData
     *
     * @return bool
     */
    public function subscriptionPurchaseIncludesNonRecurring(?array $crossSalePurchaseData = null): bool
    {
        if (!empty($crossSalePurchaseData) && is_array($crossSalePurchaseData)) {
            if (empty($crossSalePurchaseData['rebillDays']) && isset($crossSalePurchaseData['initialDays']) && $crossSalePurchaseData['initialDays'] > 0) {
                return true;
            }
        } else {
            if (empty($this->rebillFrequency) && (is_numeric($this->initialDays) && $this->initialDays > 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'purchase_id'              => $this->purchaseId(),
            'transaction_collection'   => $this->transactionCollection(),
            'session_id'               => $this->sessionId(),
            'site_id'                  => $this->siteId(),
            'status'                   => $this->status(),
            'member_id'                => $this->memberId(),
            'member_info'              => $this->memberInfo(),
            'subscription_id'          => $this->subscriptionId(),
            'entry_site_id'            => $this->entrySiteId(),
            'cross_sale_purchase_data' => $this->crossSalePurchaseData(),
            'payment'                  => $this->payment(),
            'item_id'                  => $this->itemId(),
            'bundle_id'                => $this->bundleId(),
            'add_on_id'                => $this->addOnId(),
            'subscription_username'    => $this->subscriptionUsername(),
            'subscription_password'    => $this->subscriptionPassword(),
            'rebill_frequency'         => $this->rebillFrequency(),
            'initial_days'             => $this->initialDays(),
            'atlas_code'               => $this->atlasCode(),
            'atlas_data'               => $this->atlasData(),
            'ip_address'               => $this->ipAddress(),
            'is_trial'                 => $this->isTrial(),
            'rebill_amount'            => $this->rebillAmount(),
            'amounts'                  => $this->amounts(),
            'is_existing_member'       => $this->isExistingMember(),
            'payment_method'           => $this->paymentMethod(),
            'traffic_source'           => $this->trafficSource(),
            'three_d_required'         => $this->threeDRequired(),
            'threed_version'           => $this->threedVersion(),
            'threed_frictionless'      => $this->threedFrictionless(),
            'is_third_party'           => $this->isThirdParty(),
            'is_nsf'                   => $this->isNsf(),
            'is_username_padded'       => $this->isUsernamePadded(),
            'is_imported_by_api'       => $this->isImportedByApi(),
            'skip_void_transaction'    => $this->skipVoidTransaction()
        ];
    }
}
