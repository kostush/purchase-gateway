<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit;

use ProbillerMGPG\Purchase\Init\Response\CryptoSettings;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionInitFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\State;

class PurchaseInitCommandResult
{
    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var null|string
     */
    protected $mgpgSessionId;

    /**
     * @var null|string
     */
    protected $mgpgCorrelationId;

    /**
     * @var null|string
     */
    protected $memberId;

    /**
     * @var null|string
     */
    protected $subscriptionId;

    /**
     * @var string
     */
    protected $paymentProcessorType = 'gateway';

    /**
     * @var array
     */
    protected $fraudAdvice = [];

    /**
     * @var array
     */
    protected $nuData = [];

    /**
     * @var array
     */
    protected $cryptoSettings = [];

    /**
     * @var array
     */
    protected $fraudRecommendation = [];

    /**
     * This property store many fraud recommendation objects
     * @var FraudRecommendationCollection
     */
    protected $fraudRecommendationCollection;

    /**
     * @var PaymentTemplateCollection
     */
    protected $paymentTemplateCollection;

    /**
     * @var array
     */
    protected $nextAction;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var string|null
     */
    private $forcedBiller;

    /**
     * PurchaseInitCommandResult constructor.
     * @param TokenGenerator $tokenGenerator Token generator
     * @param CryptService   $cryptService   Crypt service
     */
    public function __construct(TokenGenerator $tokenGenerator, CryptService $cryptService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->cryptService   = $cryptService;
    }

    /**
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function addFraudAdvice(FraudAdvice $fraudAdvice): void
    {
        $this->fraudAdvice['captcha']   = $fraudAdvice->isInitCaptchaAdvised();
        $this->fraudAdvice['blacklist'] = $fraudAdvice->isBlacklistedOnInit();
    }

    /**
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @return void
     */
    public function addNuData(NuDataSettings $nuDataSettings = null): void
    {
        if (is_null($nuDataSettings) || !$nuDataSettings->enabled()) {
            return;
        }
        $this->nuData['clientId']  = $nuDataSettings->clientId();
        $this->nuData['sessionId'] = $this->sessionId();
    }

    /**
     * @param FraudRecommendation $fraudRecommendation Fraud Recommendation
     * @return void
     */
    public function addFraudRecommendation(FraudRecommendation $fraudRecommendation): void
    {
        $this->fraudRecommendation = $fraudRecommendation->toArray();
    }

    /**
     * @param array|null $cryptoSettings
     */
    public function addCryptoSettings(?array $cryptoSettings): void
    {
        $this->cryptoSettings = $cryptoSettings;
    }

    /**
     * @param FraudRecommendationCollection $fraudRecommendationCollection
     */
    public function addFraudRecommendationCollection(FraudRecommendationCollection $fraudRecommendationCollection)
    {
        $this->fraudRecommendationCollection = $fraudRecommendationCollection;
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @return void
     */
    public function addPaymentTemplateCollection(PaymentTemplateCollection $paymentTemplateCollection): void
    {
        $this->paymentTemplateCollection = $paymentTemplateCollection;
    }

    /**
     * @param string $forcedBiller Forced biller
     */
    public function addForcedBiller(?string $forcedBiller): void
    {
        $this->forcedBiller = $forcedBiller;
    }

    /**
     * @return string|null
     */
    public function forcedBiller(): ?string
    {
        return $this->forcedBiller;
    }

    /**
     * @return array
     */
    public function fraudAdvice(): array
    {
        return $this->fraudAdvice;
    }

    /**
     * @return array
     */
    public function fraudRecommendation(): array
    {
        return $this->fraudRecommendation;
    }

    /**
     * @return FraudRecommendationCollection|null
     */
    public function fraudRecommendationCollection(): ?FraudRecommendationCollection
    {
        return $this->fraudRecommendationCollection;
    }

    /**
     * @return array
     */
    public function nuData(): array
    {
        return $this->nuData;
    }

    /**
     * @return array
     */
    public function nextAction(): array
    {
        return $this->nextAction;
    }

    /**
     * @return null|string
     */
    public function mgpgSessionId(): ?string
    {
        return $this->mgpgSessionId;
    }

    /**
     * @param null|string $mgpgSessionId
     */
    public function addMgpgSessionId(?string $mgpgSessionId): void
    {
        $this->mgpgSessionId = $mgpgSessionId;
    }

    /**
     * @return string|null
     */
    public function correlationId(): ?string
    {
        return $this->mgpgCorrelationId;
    }

    /**
     * @param string|null $mgpgCorrelationId
     */
    public function addCorrelationId(?string $mgpgCorrelationId): void
    {
        $this->mgpgCorrelationId = $mgpgCorrelationId;
    }

    /**
     * @param string|null $memberId
     */
    public function addMemberId(?string $memberId): void
    {
        $this->memberId = $memberId;
    }

    /**
     * @param string $subscriptionId subscriptionId
     * @return void
     */
    public function addSubscriptionId(string $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * @param string $sessionId sessionId
     * @return void
     */
    public function addSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return string|null
     */
    public function memberId(): ?string
    {
        return $this->memberId;
    }

    /**
     * @return null|string
     */
    public function subscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    /**
     * @return string
     */
    public function sessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function paymentProcessorType(): string
    {
        return $this->paymentProcessorType;
    }

    /**
     * @return PaymentTemplateCollection|null
     */
    public function paymentTemplateCollection(): ?PaymentTemplateCollection
    {
        return $this->paymentTemplateCollection;
    }

    /**
     * @param State            $state       State
     * @param Biller           $biller      Biller
     * @param FraudAdvice|null $fraudAdvice FraudAdvice
     * @param FraudRecommendation|null $fraudRecommendation FraudRecommendation
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function addNextAction(
        State $state,
        Biller $biller,
        ?FraudAdvice $fraudAdvice,
        ?FraudRecommendation $fraudRecommendation = null
    ): void {
        $nextAction = NextActionInitFactory::create(
            $state,
            $biller,
            $fraudAdvice,
            $fraudRecommendation,
            $this->buildUrl()
        );

        $this->nextAction = $nextAction->toArray();
    }

    /**
     * @param array $nextAction nextAction fields
     * @return void
     */
    public function setRawNextAction(array $nextAction)
    {
        $this->nextAction = $nextAction;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'sessionId'            => $this->sessionId(),
            'paymentProcessorType' => $this->paymentProcessorType(),
            'fraudAdvice'          => $this->fraudAdvice(),
            'fraudRecommendation'  => $this->fraudRecommendation(),
            'fraudRecommendationCollection'  => [],
        ];

        if ($this->fraudRecommendationCollection instanceof FraudRecommendationCollection) {
            $result['fraudRecommendationCollection'] = $this->fraudRecommendationCollection()->toArray();
        }

        if ($this->paymentTemplateCollection instanceof PaymentTemplateCollection) {
            $result['paymentTemplateInfo'] = $this->paymentTemplateCollection->toArray();
        }

        if (!empty($this->nuData)) {
            $result['nuData'] = $this->nuData;
        }

        if (!empty($this->nextAction)) {
            $result['nextAction'] = $this->nextAction;
        }

        return $result;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function buildUrl(): string
    {
        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt((string) $this->sessionId())
            ]
        );

        return route('thirdParty.redirect', ['jwt' => $jwt]);
    }

    /**
     * @return array|null
     */
    public function cryptoSettings(): ?array
    {
        return $this->cryptoSettings;
    }
}
