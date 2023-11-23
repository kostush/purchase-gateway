<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Log;

abstract class GenericPurchaseProcess
{
    /**
     * @var SessionId
     */
    protected $sessionId;

    /**
     * @var Purchase
     */
    protected $purchase;

    /**
     * @var PaymentTemplateCollection
     */
    protected $paymentTemplateCollection;

    /**
     * @var FraudAdvice|null
     */
    protected $fraudAdvice;

    /**
     * @var NuDataSettings|null
     */
    protected $nuDataSettings;

    /**
     * @var Cascade|null
     */
    protected $cascade;

    /**
     * @var AtlasFields
     */
    protected $atlasFields;

    /**
     * @var PaymentInfo
     */
    protected $paymentInfo;

    /**
     * @var UserInfo
     */
    protected $userInfo;

    /**
     * @var InitializedItemCollection
     */
    protected $initializedItemCollection;

    /**
     * @var int
     */
    protected $gatewaySubmitNumber = 0;

    /**
     * @var string|null
     */
    protected $memberId;

    /**
     * @var string|null
     */
    protected $purchaseId;

    /**
     * @var string|null
     */
    protected $entrySiteId;

    /**
     * @var bool
     */
    protected $existingMember;

    /**
     * @var FraudRecommendationCollection
     */
    protected $fraudRecommendationCollection;

    /**
     * @var CurrencyCode
     */
    protected $currency;

    /**
     * @var string|null
     */
    protected $redirectUrl;

    /**
     * @var string|null
     */
    protected $postbackUrl;

    /**
     * @var string|null
     */
    protected $trafficSource;

    /**
     * @var bool
     */
    protected $wasMemberIdGenerated = false;

    /**
     * @var int
     */
    protected $publicKeyIndex;

    /**
     * @var bool
     */
    protected $skipVoid;

    /**
     * @var bool
     */
    private $isUsernamePadded = false;

    /**
     * @var bool
     */
    private $creditCardWasBlacklisted = false;

    /**
     * @return int
     */
    public function publicKeyIndex(): int
    {
        return $this->publicKeyIndex;
    }

    /**
     * @return InitializedItem
     */
    public function retrieveMainPurchaseItem(): InitializedItem
    {
        foreach ($this->initializedItemCollection as $item) {
            if ($item->isCrossSale()) {
                continue;
            }

            return $item;
        }
    }

    /**
     * @return string|null
     */
    public function purchaseId(): ?string
    {
        return $this->purchaseId;
    }

    /**
     * Creates PurchaseId using value stored in db or create a new instance
     *
     * @param string|null $purchaseId Purchase Id
     * @return PurchaseId
     * @throws \Exception
     */
    public function buildPurchaseId(string $purchaseId = null): PurchaseId
    {
        if ($purchaseId !== null) {
            $this->purchaseId = $purchaseId;
        }

        if (!$this->purchaseId()) {
            $this->purchaseId = (string) PurchaseId::create();
        }

        return PurchaseId::createFromString($this->purchaseId());
    }

    /**
     * @return bool
     */
    public function isFraud(): bool
    {
        return $this->shouldShowCaptcha()
               || $this->fraudHardBlock();
    }

    /**
     * @return bool
     */
    public function shouldShowCaptcha(): bool
    {
        return !$this->fraudAdvice()->isCaptchaValidated();
    }

    /**
     * @return FraudAdvice|null
     */
    public function fraudAdvice(): ?FraudAdvice
    {
        return $this->fraudAdvice;
    }

    /**
     * @return bool
     */
    public function fraudHardBlock(): bool
    {
        if (empty($this->fraudRecommendationCollection())) {
            return false;
        }

        return $this->fraudRecommendationCollection()->hasHardBlock();
    }

    /**
     * @return FraudRecommendation|null
     * @throws \ProBillerNG\Logger\Exception
     */
    public function fraudRecommendation(): ?FraudRecommendation
    {
        if(!$this->fraudRecommendationCollection() instanceof FraudRecommendationCollection)
        {
            Log::info('FraudRecommendation FraudCollection is not a instance of FraudRecommendationCollection',
                      [$this->fraudRecommendationCollection()]);
            return null;
        }

        if($this->fraudRecommendationCollection()->isEmpty())
        {
            Log::info('FraudRecommendation FraudCollection is not empty',
                      [$this->fraudRecommendationCollection()]);
            return null;
        }

        return $this->fraudRecommendationCollection()->first();
    }

    /**
     * @param FraudRecommendation $fraudRecommendation FraudRecommendation
     * @return void
     * @deprecated
     */
    public function setFraudRecommendation(FraudRecommendation $fraudRecommendation): void
    {
        $this->fraudRecommendationCollection = new FraudRecommendationCollection([$fraudRecommendation]);
    }

    /**
     * @return FraudRecommendationCollection|null
     */
    public function fraudRecommendationCollection(): ?FraudRecommendationCollection
    {
        return $this->fraudRecommendationCollection;
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @return string|null
     */
    public function memberId(): ?string
    {
        return $this->memberId;
    }

    /**
     * @return Purchase|null
     */
    public function purchase(): ?Purchase
    {
        return $this->purchase;
    }

    /**
     * @return bool
     */
    abstract public function isCurrentBillerAvailablePaymentsMethods(): bool;

    /**
     * @return bool
     */
    public function isBlacklistedOnInit(): bool
    {
        return $this->fraudAdvice()->isBlacklistedOnInit();
    }

    /**
     * @return bool
     */
    public function isBlacklistedOnProcess(): bool
    {
        return $this->fraudAdvice()->isBlacklistedOnProcess();
    }

    /**
     * @return InitializedItem[]
     */
    public function retrieveProcessedCrossSales(): array
    {
        $crossSales = [];
        foreach ($this->initializedItemCollection as $item) {
            if ($item->isCrossSale() && $item->isSelectedCrossSale()) {
                $crossSales[] = $item;
            }
        }
        return $crossSales;
    }

    /**
     * @return Cascade|null
     */
    public function cascade(): ?Cascade
    {
        return $this->cascade;
    }

    /**
     * @return PaymentInfo|null
     */
    public function paymentInfo(): ?PaymentInfo
    {
        return $this->paymentInfo;
    }

    /**
     * @return string|null
     */
    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @return string|null
     */
    public function postbackUrl(): ?string
    {
        return $this->postbackUrl;
    }

    /**
     * @return bool
     */
    public function isUsernamePadded(): bool
    {
        return (bool) $this->isUsernamePadded;
    }

    /**
     * @param bool $wasBlacklisted Was blacklisted
     * @return void
     */
    public function setCreditCardWasBlacklisted(bool $wasBlacklisted): void
    {
        $this->creditCardWasBlacklisted = $wasBlacklisted;
    }

    /**
     * @return bool
     */
    public function creditCardWasBlacklisted(): bool
    {
        return $this->creditCardWasBlacklisted;
    }

    /**
     * @return void
     */
    public function usernamePadded(): void
    {
        $this->isUsernamePadded = true;
    }

    /**
     * If this purchase process stems from the MGPG Adaptor(true)
     * otherwise from NG(false).
     * @return bool
     */
    public function isMgpgProcess(): bool
    {
        if ($this instanceof Mgpg\PurchaseProcess || $this instanceof Mgpg\RebillUpdateProcess) {
            return true;
        }

        return false;
    }
}
