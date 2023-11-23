<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class PurchaseProcessedEnrichedEvent extends BaseEvent
{
    /**
     * Integration event name
     * @var string
     */
    public const INTEGRATION_NAME = 'ProbillerNG\\Events\\PurchaseProcessedEnriched';

    /** @var string */
    protected $purchaseId;

    /** @var string */
    protected $transactionId;

    /** @var string */
    protected $sessionId;

    /** @var string */
    protected $siteId;

    /** @var string */
    protected $memberId;

    /** @var array|null */
    protected $tax;

    /** @var string */
    protected $subscriptionId;

    /** @var array */
    protected $crossSalePurchaseData;

    /** @var string */
    protected $itemId;

    /**  @var string */
    protected $bundleId;

    /** @var string */
    protected $addOnId;

    /** @var string|null */
    protected $subscriptionUsername;

    /** @var string|null */
    protected $subscriptionPassword;

    /** @var string|null */
    protected $atlasCode;

    /** @var string|null */
    protected $atlasData;

    /** @var bool */
    protected $isTrial;

    /** @var string */
    protected $addOnType;

    /** @var int */
    protected $initialDays;

    /** @var int|null */
    protected $rebillDays;

    /** @var bool */
    protected $isUnlimited;

    /** @var bool */
    protected $isNsfOnPurchase;

    /** @var bool */
    protected $isMigrated;

    /** @var bool */
    protected $requireActiveContent;

    /** @var string|null */
    protected $email;

    /** @var float */
    protected $amount;

    /** @var float|null */
    protected $rebillAmount;

    /** @var bool */
    protected $memberExists;

    /** @var array */
    protected $memberInfo;

    /**
     * PurchaseProcessedEnrichedEvent constructor.
     *
     * @param string      $purchaseId            Purchase Id.
     * @param string      $sessionId             Session Id.
     * @param string      $siteId                Site Id.
     * @param string      $memberId              Member Id.
     * @param float       $amount                Amount
     * @param float|null  $rebillAmount          Rebill Amount
     * @param array|null  $tax                   Tax
     * @param string      $subscriptionId        Subscription Id.
     * @param array       $crossSalePurchaseData CrossSale Purchase Data
     * @param string      $itemId                Item Id.
     * @param string      $bundleId              Bundle Id.
     * @param string      $addOnId               AddOn Id.
     * @param string|null $subscriptionUsername  Subscription Username.
     * @param string|null $subscriptionPassword  Subscription Password.
     * @param string|null $email                 Email.
     * @param string|null $atlasCode             Atlas Code.
     * @param string|null $atlasData             Atlas Data.
     * @param bool        $isTrial               Is Trial.
     * @param string      $addOnType             AddOn Type.
     * @param int|null    $initialDays           Initial Days.
     * @param int|null    $rebillDays            Rebill Days.
     * @param bool        $isUnlimited           Is Unlimited.
     * @param bool        $isNsfOnPurchase       Is Nsf On Purchase.
     * @param bool        $isMigrated            Is Migrated.
     * @param bool        $requireActiveContent  Require Activate Content.
     * @param bool        $memberExists          Member exists flag.
     * @param array       $memberInfo            Member Info
     * @throws \Exception
     */
    public function __construct(
        string $purchaseId,
        string $sessionId,
        string $siteId,
        string $memberId,
        float $amount,
        ?float $rebillAmount,
        ?array $tax,
        string $subscriptionId,
        array $crossSalePurchaseData,
        string $itemId,
        string $bundleId,
        string $addOnId,
        ?string $subscriptionUsername,
        ?string $subscriptionPassword,
        ?string $email,
        ?string $atlasCode,
        ?string $atlasData,
        bool $isTrial,
        string $addOnType,
        ?int $initialDays,
        ?int $rebillDays,
        bool $isUnlimited,
        bool $isNsfOnPurchase,
        bool $isMigrated,
        bool $requireActiveContent,
        bool $memberExists,
        array $memberInfo
    ) {
        parent::__construct($purchaseId, new \DateTimeImmutable());

        $this->purchaseId            = $purchaseId;
        $this->sessionId             = $sessionId;
        $this->siteId                = $siteId;
        $this->memberId              = $memberId;
        $this->amount                = $amount;
        $this->rebillAmount          = $rebillAmount;
        $this->tax                   = $tax;
        $this->subscriptionId        = $subscriptionId;
        $this->crossSalePurchaseData = $crossSalePurchaseData;
        $this->itemId                = $itemId;
        $this->bundleId              = $bundleId;
        $this->addOnId               = $addOnId;
        $this->subscriptionUsername  = $subscriptionUsername;
        $this->subscriptionPassword  = $subscriptionPassword;
        $this->atlasCode             = $atlasCode;
        $this->atlasData             = $atlasData;
        $this->isTrial               = $isTrial;
        $this->addOnType             = $addOnType;
        $this->initialDays           = $initialDays;
        $this->rebillDays            = $rebillDays;
        $this->isUnlimited           = $isUnlimited;
        $this->isNsfOnPurchase       = $isNsfOnPurchase;
        $this->isMigrated            = $isMigrated;
        $this->requireActiveContent  = $requireActiveContent;
        $this->email                 = $email;
        $this->memberExists          = $memberExists;
        $this->memberInfo            = $memberInfo;
    }

    /**
     * @param PurchaseProcessed      $purchaseProcessed      Purchase Processed.
     * @param TransactionInformation $transactionInformation Transaction Information.
     * @param Bundle[]               $bundles                Default Bundle Flags.
     * @param Site                   $site                   Site
     * @return PurchaseProcessedEnrichedEvent
     * @throws \Exception
     */
    public static function createFromTransactionAndPurchase(
        PurchaseProcessed $purchaseProcessed,
        TransactionInformation $transactionInformation,
        array $bundles,
        Site $site
    ): self {

        $crossSales = $purchaseProcessed->crossSalePurchaseData();
        foreach ($crossSales as $key => $crossSale) {
            if (empty($crossSale['transactionCollection'])) {
                continue;
            }

            // We get last transaction because we "stop" processing further transactions (like cascading to another
            // biller as soon as we get NSF from the biller, meaning that the last transaction is always the one that
            // indicates if isNSF or not.
            $lastTransaction = end($crossSale['transactionCollection']);

            $isNsf = $lastTransaction['isNsf'] ?? false;

            // The business rule to have a cross-sell as part of the event, therefore be imported by the consumers is:
            // - Transaction approved OR
            // - Transaction declined AND entry site have NSF flag enabled AND cross-sell transaction IS NSF.
            // Because in this case, we'll import into Member Profile and there it will be imported as "expired true"
            // and IS NSF true, meaning they can offer certain products for these customers that had insufficient funds.

            // Though, we can't make this login here in the code on the positive way, because we're reading the list of
            // 'attempted cross-sells' (the ones we tried to make a purchase), and this logic is applied only for this
            // Enriched Event, so we can't go outside and change the logic to only set cross-sells under the conditions
            // above, as it would impact any other event that we may want to behave differently.

            // That being said, we have to unset the cross-sell under the following conditions to make sure that they're
            // not sent in the event body, therefore does not get imported on the consumer.
            // We don't want cross-sells transactions to be imported in the following scenarios:
            if ($lastTransaction['state'] !== Transaction::STATUS_APPROVED) {
                Log::info(
                    'Cross-sell transaction state is not approved.',
                    [
                        'crossSellTransactionId'    => $lastTransaction['transactionId'],
                        'crossSellTransactionState' => $lastTransaction['state']
                    ]
                );

                // NSF feature for cross-sell is intended to be based on 'entry site' (site for the main purchase)
                // not the cross-sell site.
                // Reference: https://wiki.mgcorp.co/display/PROBILLER/Purchase+Gateway+support+for+NSF
                // Under "Business Flow" section it's stated the following:
                // "The feature needs to be enabled for the entry site. When enabled, NSF flow will apply to the main
                // purchase and to the cross sale."
                if (!$site->isNsfSupported()) {
                    Log::info(
                        "Entry site doesn't have 'NSF feature flag enabled', so cross-sell got unset from event.",
                        ['entrySiteId' => $site->id()]
                    );
                    unset($crossSales[$key]);
                    continue;
                }

                // We're not interested to have a cross-sell that has been declined by whatever reason that NSF.
                // This is intended to import only if was declined, have entry site NSF flag enabled and the reason for
                // failure IS Non Sufficient Funds.
                if ($isNsf === false) {
                    Log::info('Cross-sell purchase is NOT NSF.');
                    unset($crossSales[$key]);
                    continue;
                }
            }

            Log::info('Cross-sell purchase was successful.');
            $crossSaleBundle = $bundles[$crossSale['bundleId']];

            // TODO remove this once we MP Gateway supports our signature
            if (!isset($crossSale['addOnId']) && isset($crossSale['addonId'])) {
                $crossSales[$key]['addOnId'] = $crossSale['addonId'];
            }
            $crossSales[$key]['addOnType']            = (string) $crossSaleBundle->addonType();
            $crossSales[$key]['rebillStartDays']      = $crossSale['initialDays'] ?? 0;
            $crossSales[$key]['rebillFrequency']      = $crossSale['rebillDays'] ?? 0;
            $crossSales[$key]['rebillAmount']         = $crossSale['rebillAmount'] ?? 0;
            $crossSales[$key]['transactionId']        = $lastTransaction['transactionId'];
            $crossSales[$key]['status']               = $lastTransaction['state'];
            $crossSales[$key]['requireActiveContent'] = $crossSaleBundle->isRequireActiveContent();
            $crossSales[$key]['isNsfOnPurchase']      = $isNsf;
            $crossSales[$key]['isUnlimited']          = false;
            if (!isset($crossSale['itemId'])) {
                $crossSales[$key]['itemId'] = $crossSales[$key]['transactionId'];
            }
            if (!isset($crossSale['isTrial'])) {
                $crossSales[$key]['isTrial'] = false;
            }
        }

        $siteId = (
        !empty($purchaseProcessed->entrySiteId()) ? $purchaseProcessed->entrySiteId() : $purchaseProcessed->siteId()
        );

        $bundle = $bundles[$purchaseProcessed->bundleId()];

        try {
            $amount = $purchaseProcessed->amount();
        } catch (\Throwable $exception) {
            $amount = $transactionInformation->amount();
        }

        try {
            $rebillAmount = $purchaseProcessed->rebillAmount();
        } catch (\Throwable $exception) {
            $rebillAmount = $transactionInformation->rebillAmount();
        }

        try {
            $itemId = $purchaseProcessed->itemId();
        } catch (\Throwable $exception) {
            $itemId = $purchaseProcessed->lastTransactionId();
        }

        return new self(
            $purchaseProcessed->purchaseId(),
            $purchaseProcessed->sessionId(),
            $siteId,
            $purchaseProcessed->memberId(),
            $amount,
            $rebillAmount,
            $purchaseProcessed->amounts(),
            $purchaseProcessed->subscriptionId(),
            $crossSales,
            $itemId,
            $purchaseProcessed->bundleId(),
            $purchaseProcessed->addOnId(),
            $purchaseProcessed->subscriptionUsername(),
            $purchaseProcessed->subscriptionPassword(),
            $purchaseProcessed->memberInfo()['email'],
            $purchaseProcessed->atlasCode(),
            $purchaseProcessed->atlasData(),
            $purchaseProcessed->isTrial(),
            (string) $bundle->addonType(),
            $purchaseProcessed->initialDays(), //initial days
            $transactionInformation->rebillFrequency(), //rebill days
            false, // Will be received in purchaseDetails once implemented
            $transactionInformation->isNsf(),
            false,
            $bundle->isRequireActiveContent(),
            $purchaseProcessed->isExistingMember(),
            $purchaseProcessed->memberInfo() ?? []
        );
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function requireActiveContent(): bool
    {
        return $this->requireActiveContent;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function purchaseId(): string
    {
        return $this->purchaseId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function siteId(): string
    {
        return $this->siteId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function memberId(): string
    {
        return $this->memberId;
    }

    /**
     * @return array|null
     * @codeCoverageIgnore
     */
    public function tax(): ?array
    {
        return $this->tax;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function subscriptionId(): string
    {
        return $this->subscriptionId;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function crossSalePurchaseData(): array
    {
        foreach ($this->crossSalePurchaseData as $k => $crossSale) {
            if (!empty($crossSale['amounts'])) {
                $this->crossSalePurchaseData[$k]['tax'] = $crossSale['amounts'] ?? [];
                unset($this->crossSalePurchaseData[$k]['amounts']);
            }
        }
        return $this->crossSalePurchaseData;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function itemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function bundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function addOnId(): string
    {
        return $this->addOnId;
    }

    /**
     * @return string|null
     * @codeCoverageIgnore
     */
    public function subscriptionUsername(): ?string
    {
        return $this->subscriptionUsername;
    }

    /**
     * @return string|null
     * @codeCoverageIgnore
     */
    public function subscriptionPassword(): ?string
    {
        return $this->subscriptionPassword;
    }

    /**
     * @return string|null
     * @codeCoverageIgnore
     */
    public function atlasCode(): ?string
    {
        return $this->atlasCode;
    }


    /**
     * @return string|null
     * @codeCoverageIgnore
     */
    public function atlasData(): ?string
    {
        return $this->atlasData;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function isTrial(): bool
    {
        return $this->isTrial;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function addOnType(): string
    {
        return $this->addOnType;
    }

    /**
     * @return int|null
     * @codeCoverageIgnore
     */
    public function initialDays(): ?int
    {
        return $this->initialDays;
    }

    /**
     * @return int|null
     * @codeCoverageIgnore
     */
    public function rebillDays(): ?int
    {
        return $this->rebillDays;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function isUnlimited(): bool
    {
        return $this->isUnlimited;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function isNsfOnPurchase(): bool
    {
        return $this->isNsfOnPurchase;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function isMigrated(): bool
    {
        return $this->isMigrated;
    }

    /**
     * @return string|null
     * @codeCoverageIgnore
     */
    public function email(): ?string
    {
        return $this->email;
    }

    /**
     * @return float
     * @codeCoverageIgnore
     */
    public function amount(): ?float
    {
        return $this->amount;
    }

    /**
     * @return float|null
     * @codeCoverageIgnore
     */
    public function rebillAmount(): ?float
    {
        return $this->rebillAmount;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function memberExists(): bool
    {
        return $this->memberExists;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function memberInfo(): array
    {
        return $this->memberInfo;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'                  => self::INTEGRATION_NAME,
            'purchaseId'            => $this->purchaseId(),
            'sessionId'             => $this->sessionId(),
            'memberId'              => $this->memberId(),
            'siteId'                => $this->siteId(),
            'amount'                => $this->amount(),
            'rebillAmount'          => $this->rebillAmount(),
            'tax'                   => $this->tax(),
            'subscriptionId'        => $this->subscriptionId(),
            'crossSellPurchaseData' => $this->crossSalePurchaseData(),
            'itemId'                => $this->itemId(),
            'bundleId'              => $this->bundleId(),
            'addOnId'               => $this->addOnId(),
            'subscriptionUsername'  => $this->subscriptionUsername(),
            'subscriptionPassword'  => $this->subscriptionPassword(),
            'email'                 => $this->email(),
            'atlasCode'             => $this->atlasCode(),
            'atlasData'             => $this->atlasData(),
            'isTrial'               => $this->isTrial(),
            'addOnType'             => $this->addOnType(),
            'initialDays'           => $this->initialDays(),
            'rebillDays'            => $this->rebillDays(),
            'isUnlimited'           => $this->isUnlimited(),
            'isNsfOnPurchase'       => $this->isNsfOnPurchase(),
            'isMigrated'            => $this->isMigrated(),
            'requireActiveContent'  => $this->requireActiveContent(),
            'memberExists'          => $this->memberExists(),
            'occurredOn'            => $this->occurredOn(),
            'memberInfo'            => $this->memberInfo()
        ];
    }
}
