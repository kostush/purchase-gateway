<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use Illuminate\Support\Str;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotProcessPurchaseWithoutCaptchaValidationException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Application\Services\SessionVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidEntrySiteSubscriptionCombinationException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ItemCouldNotBeRestoredException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ItemMissingFromCollection;
use ProBillerNG\PurchaseGateway\Domain\RemovedBillerCollectionForThreeDS;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\State;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentInfoFactoryService;
use Throwable;

class PurchaseProcess extends GenericPurchaseProcess
{
    /**
     * @var AbstractState
     */
    protected $state;

    /**
     * @param SessionId                 $sessionId                 The session id
     * @param State                     $state                     State
     * @param AtlasFields               $atlasFields               The atlas fields
     * @param int                       $publicKeyIndex            The public key index
     * @param UserInfo                  $userInfo                  The user info
     * @param PaymentInfo               $paymentInfo               The payment info
     * @param InitializedItemCollection $initializedItemCollection The initialized item collection
     * @param string|null               $memberId                  $memberId
     * @param string|null               $entrySiteId               $entrySiteId
     * @param bool                      $existingMember            Existing member purchase flag
     * @param CurrencyCode              $currency                  Currency
     * @param string|null               $redirectUrl               Redirect url
     * @param string|null               $postbackUrl               Postback url
     * @param string|null               $trafficSource             Traffic source
     * @param bool                      $skipVoid                  Skip void transactions
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(
        SessionId $sessionId,
        State $state,
        AtlasFields $atlasFields,
        int $publicKeyIndex,
        UserInfo $userInfo,
        PaymentInfo $paymentInfo,
        InitializedItemCollection $initializedItemCollection,
        ?string $memberId,
        ?string $entrySiteId,
        bool $existingMember,
        CurrencyCode $currency,
        ?string $redirectUrl,
        ?string $postbackUrl,
        ?string $trafficSource,
        bool $skipVoid = false
    ) {
        $this->sessionId                 = $sessionId;
        $this->state                     = $state;
        $this->atlasFields               = $atlasFields;
        $this->publicKeyIndex            = $publicKeyIndex;
        $this->userInfo                  = $userInfo;
        $this->paymentInfo               = $paymentInfo;
        $this->initializedItemCollection = $initializedItemCollection;
        $this->memberId                  = $memberId;
        $this->entrySiteId               = $entrySiteId;
        $this->existingMember            = $existingMember;
        $this->currency                  = $currency;
        $this->redirectUrl               = $redirectUrl;
        $this->postbackUrl               = $postbackUrl;
        $this->trafficSource             = $trafficSource;
        $this->skipVoid                  = $skipVoid;

        Log::info('Purchase Process created', ['sessionId' => (string) $sessionId]);
    }

    /**
     * @param SessionId                 $sessionId                 The session id
     * @param AtlasFields               $atlasFields               The atlas fields
     * @param int                       $publicKeyIndex            The public key index
     * @param UserInfo                  $userInfo                  The user info
     * @param PaymentInfo               $paymentInfo               The payment info
     * @param InitializedItemCollection $initializedItemCollection The initialized item collection
     * @param string|null               $memberId                  $memberId
     * @param string|null               $entrySiteId               $entrySiteId
     * @param CurrencyCode              $currency                  Currency
     * @param string|null               $redirectUrl               Redirect Url
     * @param string|null               $postbackUrl               Postback Url
     * @param string|null               $trafficSource             Traffic source
     * @param bool                      $skipVoid                  Skip Void transactions
     * @return PurchaseProcess
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        SessionId $sessionId,
        AtlasFields $atlasFields,
        int $publicKeyIndex,
        UserInfo $userInfo,
        PaymentInfo $paymentInfo,
        InitializedItemCollection $initializedItemCollection,
        ?string $memberId,
        ?string $entrySiteId,
        CurrencyCode $currency,
        ?string $redirectUrl,
        ?string $postbackUrl,
        ?string $trafficSource,
        bool $skipVoid = false
    ): PurchaseProcess {
        $existingMember = !empty($memberId);

        return new static(
            $sessionId,
            new Created(),
            $atlasFields,
            $publicKeyIndex,
            $userInfo,
            $paymentInfo,
            $initializedItemCollection,
            $memberId,
            $entrySiteId,
            $existingMember,
            $currency,
            $redirectUrl,
            $postbackUrl,
            $trafficSource,
            $skipVoid
        );
    }

    /**
     * @param array $sessionInfo Session Info
     *
     * @return PurchaseProcess
     *
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception\InvalidCurrency
     * @throws Exception\InvalidIpException
     * @throws Exception\InvalidUserInfoCountry
     * @throws Exception\InvalidUserInfoEmail
     * @throws Exception\InvalidUserInfoFirstName
     * @throws Exception\InvalidUserInfoLastName
     * @throws Exception\InvalidUserInfoPassword
     * @throws Exception\InvalidUserInfoPhoneNumber
     * @throws Exception\InvalidUserInfoUsername
     * @throws Exception\InvalidZipCodeException
     * @throws Exception\UnsupportedPaymentMethodException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws Exception\ValidationException
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws UnknownBillerNameException
     * @throws \Exception
     */
    public static function restore(array $sessionInfo): self
    {
        $email = !empty($sessionInfo['userInfo']['email']) ? Email::create($sessionInfo['userInfo']['email']) : null;

        $userInfo = UserInfo::create(
            CountryCode::create($sessionInfo['userInfo']['country']),
            Ip::create($sessionInfo['userInfo']['ipAddress']),
            $email
        );

        // Setting country code detected by ip in order to keep it for further usage (e.g. Email cancellation verbiage for US)
        $userInfo->setCountryCodeDetectedByIp(CountryCode::create($sessionInfo['userInfo']['country']));

        $purchaseProcess = new static(
            SessionId::createFromString($sessionInfo['sessionId']),
            AbstractState::restore($sessionInfo['state']),
            AtlasFields::create(
                $sessionInfo['atlasFields']['atlasCode'],
                $sessionInfo['atlasFields']['atlasData']
            ),
            $sessionInfo['publicKeyIndex'],
            $userInfo,
            PaymentInfoFactoryService::create(
                $sessionInfo['paymentType'],
                $sessionInfo['paymentMethod'],
                $sessionInfo['paymentTemplateId'],
                $sessionInfo['paymentTemplateId']
            ),
            new InitializedItemCollection(),
            $sessionInfo['memberId'],
            $sessionInfo['entrySiteId'],
            $sessionInfo['existingMember'],
            CurrencyCode::create($sessionInfo['currency']),
            $sessionInfo['redirectUrl'],
            $sessionInfo['postbackUrl'],
            $sessionInfo['trafficSource'],
            $sessionInfo['skipVoid'] ?? false
        );

        $purchaseProcess->setGatewaySubmitNumber($sessionInfo['gatewaySubmitNumber']);

        foreach ($sessionInfo['initializedItemCollection'] as $item) {
            $item['amount'] = $item['initialAmount'];
            $purchaseProcess->restoreItem($item);
        }

        self::restoreUserInfo($purchaseProcess, $sessionInfo);

        self::restoreFraudAdvice($purchaseProcess, $sessionInfo);

        self::restoreNuDataSettings($purchaseProcess, $sessionInfo);

        self::restorePaymentTemplates($purchaseProcess, $sessionInfo);

        self::restoreCascade($purchaseProcess, $sessionInfo);

        $fraudCollection = FraudRecommendationCollection::createFromArray($sessionInfo['fraudRecommendationCollection']);

        $purchaseProcess->setFraudRecommendationCollection($fraudCollection);

        $purchaseProcess->buildMemberId();
        $purchaseProcess->buildPurchaseId($sessionInfo['purchaseId']);
        $purchaseProcess->setCreditCardWasBlacklisted($sessionInfo['creditCardWasBlacklisted']);

        return $purchaseProcess;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param array           $sessionInfo     Session info
     * @return void
     * @throws Exception\InvalidUserInfoFirstName
     * @throws Exception\InvalidUserInfoLastName
     * @throws Exception\InvalidUserInfoPassword
     * @throws Exception\InvalidUserInfoPhoneNumber
     * @throws Exception\InvalidUserInfoUsername
     * @throws Exception\InvalidZipCodeException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected static function restoreUserInfo(PurchaseProcess $purchaseProcess, array $sessionInfo): void
    {
        if (!empty($sessionInfo['userInfo']['phoneNumber'])) {
            $purchaseProcess->userInfo()->setPhoneNumber(PhoneNumber::create($sessionInfo['userInfo']['phoneNumber']));
        }

        if (!empty($sessionInfo['userInfo']['username'])) {
            $purchaseProcess->userInfo()->setUsername(Username::create($sessionInfo['userInfo']['username']));
        }

        if (!empty($sessionInfo['userInfo']['password'])) {
            $purchaseProcess->userInfo()->setPassword(Password::create($sessionInfo['userInfo']['password']));
        }

        if (!empty($sessionInfo['userInfo']['firstName'])) {
            $purchaseProcess->userInfo()->setFirstName(FirstName::create($sessionInfo['userInfo']['firstName']));
        }

        if (!empty($sessionInfo['userInfo']['lastName'])) {
            $purchaseProcess->userInfo()->setLastName(LastName::create($sessionInfo['userInfo']['lastName']));
        }

        if (!empty($sessionInfo['userInfo']['zipCode'])) {
            $purchaseProcess->userInfo()->setZipCode(Zip::create($sessionInfo['userInfo']['zipCode']));
        }

        if (!empty($sessionInfo['userInfo']['address'])) {
            $purchaseProcess->userInfo()->setAddress($sessionInfo['userInfo']['address']);
        }

        if (!empty($sessionInfo['userInfo']['city'])) {
            $purchaseProcess->userInfo()->setCity($sessionInfo['userInfo']['city']);
        }

        if (!empty($sessionInfo['userInfo']['state'])) {
            $purchaseProcess->userInfo()->setState($sessionInfo['userInfo']['state']);
        }
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param array           $sessionInfo     Session info
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws UnknownBillerNameException
     */
    protected static function restoreCascade(PurchaseProcess $purchaseProcess, array $sessionInfo): void
    {
        $billerCollection = new BillerCollection();

        $currentBillerName = $sessionInfo['cascade']['currentBiller'];

        foreach ($sessionInfo['cascade']['billers'] as $billerName) {
            $billerCollection->add(BillerFactoryService::create($billerName));
        }

        $purchaseProcess->setCascade(
            Cascade::create(
                $billerCollection,
                BillerFactoryService::create($currentBillerName),
                $sessionInfo['cascade']['currentBillerSubmit'],
                $sessionInfo['cascade']['currentBillerPosition'],
                self::removedBillersFor3DSonCascade($sessionInfo['cascade'])
            )
        );
    }

    /**
     * @param array|null $cascade
     * @return RemovedBillerCollectionForThreeDS
     */
    protected static function removedBillersFor3DSonCascade(?array $cascade
    ): RemovedBillerCollectionForThreeDS {
        $removedBillerCollection = new RemovedBillerCollectionForThreeDS();
        if (empty($cascade) || !isset($cascade['removedBillersFor3DS'])) {
            return $removedBillerCollection;
        }

        foreach ($cascade['removedBillersFor3DS'] as $billerName) {
            $removedBillerCollection->add($billerName);
        }

        return $removedBillerCollection;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param array           $sessionInfo     Session info
     *
     * @return void
     *
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception\InvalidIpException
     * @throws Exception\InvalidUserInfoEmail
     * @throws Exception\InvalidZipCodeException
     * @throws Exception\ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected static function restoreFraudAdvice(PurchaseProcess $purchaseProcess, array $sessionInfo): void
    {
        $ip    = !empty($sessionInfo['fraudAdvice']['ip']) ? Ip::create($sessionInfo['fraudAdvice']['ip']) : null;
        $email = !empty($sessionInfo['fraudAdvice']['email']) ? (
        Email::create($sessionInfo['fraudAdvice']['email'])
        ) : null;
        $zip   = !empty($sessionInfo['fraudAdvice']['zip']) ? Zip::create($sessionInfo['fraudAdvice']['zip']) : null;
        $bin   = !empty($sessionInfo['fraudAdvice']['bin']) ? (
        Bin::createFromString($sessionInfo['fraudAdvice']['bin'])
        ) : null;

        $fraudAdvice = FraudAdvice::create($ip, $email, $zip, $bin);

        if ($sessionInfo['fraudAdvice']['initCaptchaAdvised']) {
            $fraudAdvice->markInitCaptchaAdvised();
            if ($sessionInfo['fraudAdvice']['initCaptchaValidated']) {
                $fraudAdvice->validateInitCaptcha();
            }
        }

        if ($sessionInfo['fraudAdvice']['processCaptchaAdvised']) {
            $fraudAdvice->markProcessCaptchaAdvised();
            if ($sessionInfo['fraudAdvice']['processCaptchaValidated']) {
                $fraudAdvice->validateProcessCaptcha();
            }
        }

        if ($sessionInfo['fraudAdvice']['blacklistedOnInit']) {
            $fraudAdvice->markBlacklistedOnInit();
        }

        if ($sessionInfo['fraudAdvice']['blacklistedOnProcess']) {
            $fraudAdvice->markBlacklistedOnProcess();
        }

        for ($timesBlacklisted = 0; $timesBlacklisted < $sessionInfo['fraudAdvice']['timesBlacklisted']; $timesBlacklisted++) {
            $fraudAdvice->increaseTimesBlacklisted();
        }

        if ($sessionInfo['fraudAdvice']['forceThreeDOnInit'] === true) {
            $fraudAdvice->markForceThreeDOnInit();
        }

        if ($sessionInfo['fraudAdvice']['forceThreeDOnProcess'] === true) {
            $fraudAdvice->markForceThreeDOnProcess();
        }

        if ($sessionInfo['fraudAdvice']['detectThreeDUsage'] === true) {
            $fraudAdvice->markDetectThreeDUsage();
        }

        $purchaseProcess->setFraudAdvice($fraudAdvice);
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @param array           $sessionInfo     Session Info
     * @return void
     */
    protected static function restoreNuDataSettings(PurchaseProcess $purchaseProcess, array $sessionInfo): void
    {
        if (empty($sessionInfo['nuDataSettings']['clientId'])) {
            return;
        }

        $nuDataSettings = NuDataSettings::create(
            $sessionInfo['nuDataSettings']['clientId'],
            $sessionInfo['nuDataSettings']['url'],
            $sessionInfo['nuDataSettings']['enabled']
        );

        $purchaseProcess->setNuDataSettings($nuDataSettings);
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param array           $sessionInfo     Session info
     *
     * @return void
     */
    protected static function restorePaymentTemplates(PurchaseProcess $purchaseProcess, array $sessionInfo): void
    {
        if (is_null($sessionInfo['paymentTemplateCollection'])) {
            return;
        }

        $paymentTemplateCollection = new PaymentTemplateCollection();

        foreach ($sessionInfo['paymentTemplateCollection'] as $paymentTemplateData) {
            $paymentTemplate = PaymentTemplate::create(
                $paymentTemplateData['templateId'],
                $paymentTemplateData['firstSix'],
                '',
                $paymentTemplateData['expirationYear'],
                $paymentTemplateData['expirationMonth'],
                $paymentTemplateData['lastUsedDate'],
                $paymentTemplateData['createdAt'],
                $paymentTemplateData['billerName'],
                []
            );

            if (isset($paymentTemplateData['requiresIdentityVerification'])
                && !$paymentTemplateData['requiresIdentityVerification']
            ) {
                $paymentTemplate->setIsSafe(true);
            }

            $paymentTemplateCollection->offsetSet(
                $paymentTemplateData['templateId'],
                $paymentTemplate
            );
        }

        $purchaseProcess->setPaymentTemplateCollection($paymentTemplateCollection);
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function initStateAccordingToFraudAdvice(): void
    {
        if (!$this->fraudAdvice() instanceof FraudAdvice) {
            throw new IllegalStateTransitionException();
        }

        if ($this->fraudAdvice()->isInitCaptchaAdvised()
            || $this->isBlacklistedOnInit()
        ) {
            $this->blockDueToFraudAdvice();
        } else {
            $this->validate();
        }
    }

    /**
     * @param Purchase $purchase Purchase
     * @return void
     */
    public function setPurchase(Purchase $purchase): void
    {
        $this->purchase = $purchase;
    }

    /**
     * @param Cascade $cascade The cascade object
     * @return void
     */
    public function setCascade(Cascade $cascade): void
    {
        $this->cascade = $cascade;
    }

    /**
     * @param PaymentTemplateCollection|null $paymentTemplateCollection PaymentTemplateCollection
     * @return void
     */
    public function setPaymentTemplateCollection(?PaymentTemplateCollection $paymentTemplateCollection): void
    {
        $this->paymentTemplateCollection = $paymentTemplateCollection;
    }

    /**
     * @param int $attemptNumber The submit attempt
     * @return void
     */
    private function setGatewaySubmitNumber(int $attemptNumber): void
    {
        $this->gatewaySubmitNumber = $attemptNumber;
    }

    /**
     * @param FraudAdvice $fraudAdvice The fraud advice object
     * @return void
     */
    public function setFraudAdvice(FraudAdvice $fraudAdvice): void
    {
        $this->fraudAdvice = $fraudAdvice;
    }

    /**
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @return void
     */
    public function setNuDataSettings(NuDataSettings $nuDataSettings): void
    {
        $this->nuDataSettings = $nuDataSettings;
    }

    /**
     * @param FraudRecommendationCollection $fraudRecommendationCollection Fraud Recommendation
     * @return void
     */
    public function setFraudRecommendationCollection(FraudRecommendationCollection $fraudRecommendationCollection): void
    {
        $this->fraudRecommendationCollection = $fraudRecommendationCollection;
    }

    /**
     * @param PaymentInfo $paymentInfo PaymentInfo
     * @return void
     */
    public function setPaymentInfo(PaymentInfo $paymentInfo): void
    {
        $this->paymentInfo = $paymentInfo;
    }

    /**
     * @param Transaction $transaction The transaction
     * @param string      $itemId      The item Id
     * @return void
     * @throws \Exception
     */
    public function addTransactionToItem(Transaction $transaction, string $itemId): void
    {
        if (!$this->initializedItemCollection()->containsKey($itemId)) {
            throw new ItemMissingFromCollection();
        }

        /** @var InitializedItem $initializedItem */
        $initializedItem = $this->initializedItemCollection()->offsetGet($itemId);
        $initializedItem->transactionCollection()->add($transaction);
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception\IllegalStateTransitionException
     * @throws Exception
     */
    public function validateInitCaptcha(): void
    {
        $this->fraudAdvice()->validateInitCaptcha();

        if ($this->isBlockedDueToFraudAdvice() && !$this->isBlacklistedOnInit()) {
            $this->state = $this->state->validate();

            // need to reset the member id,
            // on init captcha validation we don't need the generated member id
            $this->resetMemberId();

            Log::info('Purchase process state changed to valid');
        }
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception\IllegalStateTransitionException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception
     */
    public function validateProcessCaptcha(): void
    {
        $this->fraudAdvice()->validateProcessCaptcha();

        $stateIsBlocked          = $this->isBlockedDueToFraudAdvice();
        $notBlacklistedOnProcess = !$this->isBlacklistedOnProcess();
        $notBlacklistedOnInit    = !$this->isBlacklistedOnInit();

        if ($stateIsBlocked && $notBlacklistedOnProcess && $notBlacklistedOnInit) {
            $this->state = $this->state->validate();
            $this->resetMemberId();

            Log::info('Purchase process state changed to valid');
        }
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     * @throws Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process(): void
    {
        if (!$this->fraudAdvice()->isCaptchaValidated()) {
            throw new CannotProcessPurchaseWithoutCaptchaValidationException();
        }
        $this->state = $this->state->startProcessing();
        Log::info('Purchase process state changed to start processing');
    }

    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->state()->processed();
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->state()->valid();
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state()->pending();
    }

    /**
     * @return bool
     */
    public function isRedirected(): bool
    {
        return $this->state()->redirected();
    }

    /**
     * @return bool
     */
    public function isThreeDAuthenticated(): bool
    {
        return $this->state()->threeDAuthenticated();
    }

    /**
     * @return bool
     */
    public function isThreeDLookupPerformed(): bool
    {
        return $this->state()->threeDLookupPerformed();
    }

    /**
     * @return bool
     */
    public function isBlockedDueToFraudAdvice(): bool
    {
        return $this->state->blockedDueToFraudAdvice();
    }

    /**
     * @return void
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function postProcessing(): void
    {
        $mainPurchase = $this->retrieveMainPurchaseItem();

        if ($mainPurchase->wasItemPurchasePending()) {
            Log::info("PostProcessing main item last state was pending.");
            $this->startPending();
            return;
        }

        if ($mainPurchase->wasItemNsfPurchase()) {
            Log::info("PostProcessing main item transaction was NSF.");
            $this->finishProcessing();
            return;
        }

        $hasSubmitsLeft            = $this->cascade()->hasSubmitsLeft();
        $wasItemPurchaseSuccessful = $mainPurchase->wasItemPurchaseSuccessful();
        if (!$hasSubmitsLeft || $wasItemPurchaseSuccessful) {
            Log::info(
                "PostProcessing cascade does not have submits left or the purchase was successful.",
                [
                    'hasSubmitsLeft'            => $hasSubmitsLeft,
                    'wasItemPurchaseSuccessful' => $wasItemPurchaseSuccessful,
                ]
            );
            $this->finishProcessing();

            return;
        }

        $this->validate();

        $this->updateProcessStateBasedOnCascadeRemainingBillers();
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws IllegalStateTransitionException
     */
    public function wasThreeDStarted(): void
    {
        if ($this->isThreeDAuthenticated() || $this->isPending()) {
            throw new IllegalStateTransitionException(null, '3DS already started');
        }
    }

    /**
     * @return void
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function wasStartedWithThirdPartyBiller(): void
    {
        if ($this->isPending() || $this->isRedirected()) {
            throw new IllegalStateTransitionException(
                null,
                'The process with third party biller already started.'
            );
        }
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function startPendingThirdPartyProcess(): void
    {
        $this->startPending();
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validate(): void
    {
        $this->state = $this->state()->validate();
        Log::info('Purchase process state changed to valid');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function startProcessing(): void
    {
        $this->state = $this->state()->startProcessing();
        Log::info('Purchase process state changed to start processing');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function startPending(): void
    {
        $this->state = $this->state()->startPending();
        Log::info('Purchase process state changed to start pending');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function authenticateThreeD(): void
    {
        $this->state = $this->state()->authenticateThreeD();
        Log::info('Purchase process state changed to authenticateThreeD');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function performThreeDLookup(): void
    {
        $this->state = $this->state()->performThreeDLookup();
        Log::info('Purchase process state changed to ThreeDLookupPerformed');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function redirect(): void
    {
        $this->state = $this->state()->redirect();
        Log::info('Purchase process state changed to redirected');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function finishProcessing(): void
    {
        $this->state = $this->state()->finishProcessing();

        $this->gatewaySubmitNumber++;
        Log::info('Purchase process state changed to finish processing');
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function blockDueToFraudAdvice(): void
    {
        $this->state = $this->state()->blockDueToFraudAdvice();
        Log::info('Set purchase process state to blocked, due to fraud advice');

        if ($this->isBlacklistedOnProcess()) {
            $this->fraudAdvice()->increaseTimesBlacklisted();
        }
    }

    /**
     * @return bool
     */
    public function shouldShowCaptcha(): bool
    {
        return !$this->fraudAdvice()->isCaptchaValidated();
    }

    /**
     * @return bool
     */
    public function shouldBlockProcess(): bool
    {
        return $this->fraudAdvice()->shouldBlockProcess() || $this->fraudHardBlock();
    }

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
     * @return bool
     */
    public function isFraud(): bool
    {
        return $this->shouldShowCaptcha()
               || $this->fraudHardBlock();
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
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
    public function skipVoid(): bool
    {
        return $this->skipVoid;
    }

    /**
     * @return InitializedItemCollection
     */
    public function initializedItemCollection(): InitializedItemCollection
    {
        return $this->initializedItemCollection;
    }

    /**
     * @return AtlasFields
     */
    public function atlasFields(): AtlasFields
    {
        return $this->atlasFields;
    }

    /**
     * @return Cascade|null
     */
    public function cascade(): ?Cascade
    {
        return $this->cascade;
    }

    /**
     * @return PaymentTemplateCollection|null
     */
    public function paymentTemplateCollection(): ?PaymentTemplateCollection
    {
        return $this->paymentTemplateCollection;
    }

    /**
     * @return FraudAdvice|null
     */
    public function fraudAdvice(): ?FraudAdvice
    {
        return $this->fraudAdvice;
    }

    /**
     * @return NuDataSettings|null
     */
    public function nuDataSettings(): ?NuDataSettings
    {
        return $this->nuDataSettings;
    }

    /**
     * @return PaymentInfo
     */
    public function paymentInfo(): PaymentInfo
    {
        return $this->paymentInfo;
    }

    /**
     * @return UserInfo
     */
    public function userInfo(): UserInfo
    {
        return $this->userInfo;
    }

    /**
     * @return AbstractState
     */
    public function state(): AbstractState
    {
        return $this->state;
    }

    /**
     * @return int
     */
    public function publicKeyIndex(): int
    {
        return $this->publicKeyIndex;
    }

    /**
     * @return int
     */
    public function gatewaySubmitNumber(): int
    {
        return $this->gatewaySubmitNumber;
    }

    /**
     * @return int
     */
    public function submitNumberIncreased(): int
    {
        return ($this->gatewaySubmitNumber + 1);
    }

    /**
     * @return string|null
     */
    public function memberId(): ?string
    {
        return $this->memberId;
    }

    /**
     * @return string|null
     */
    public function purchaseId(): ?string
    {
        return $this->purchaseId;
    }

    /**
     * @return bool
     */
    public function wasMemberIdGenerated(): bool
    {
        return $this->wasMemberIdGenerated;
    }

    /**
     * @return string|null
     */
    public function entrySiteId(): ?string
    {
        return $this->entrySiteId;
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function incrementGatewaySubmitNumber(): void
    {
        if ($this->isProcessed() || $this->isBlockedDueToFraudAdvice() || $this->isPending()) {
            return;
        }

        $this->gatewaySubmitNumber++;

        if (!$this->cascade()->hasSubmitsLeft()) {
            $this->finishProcessing();
        }
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function incrementGatewaySubmitNumberIfValid(): void
    {
        if (!$this->isValid()) {
            return;
        }

        $this->incrementGatewaySubmitNumber();
    }

    /**
     * @param array $item        The item array
     * @param bool  $isCrossSale The cross sale flag
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     * @throws InvalidAmountException
     * @throws \Exception
     */
    public function initializeItem(
        array $item,
        bool $isCrossSale = false
    ): void {
        Log::info('Initializing item', ['item' => $item]);

        $properties = $this->buildItemProperties($item, $isCrossSale);

        $item = InitializedItem::create(
            SiteId::createFromString((string) $item['siteId']),
            BundleId::createFromString((string) $item['bundleId']),
            AddonId::createFromString((string) $item['addonId']),
            $properties['bundleChargeInformation'],
            $properties['taxInformation'],
            $properties['isCrossSale'],
            !empty($item['isTrial']) ? (bool) $item['isTrial'] : false,
            !empty($item['subscriptionId']) ? (string) $item['subscriptionId'] : null
        );

        $this->initializedItemCollection->offsetSet((string) $item->itemId(), $item);
    }

    /**
     * @param array $item        The item array
     * @param bool  $isCrossSale The cross sale flag
     * @return array
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function buildItemProperties(array $item, bool $isCrossSale = false): array
    {
        Log::info('Building item properties', ['item' => $item]);

        $initialTaxBreakdown = null;
        $taxInformation      = null;

        $isCrossSale = $item['isCrossSale'] ?? $isCrossSale;

        if (!empty($item['tax']) && isset($item['tax']['initialAmount'])) {
            $initialTaxBreakdown = TaxBreakdown::create(
                Amount::create((float) $item['tax']['initialAmount']['beforeTaxes']),
                Amount::create((float) $item['tax']['initialAmount']['taxes']),
                Amount::create((float) $item['tax']['initialAmount']['afterTaxes'])
            );

            $taxInformation = TaxInformation::create(
                !empty($item['tax']['taxName']) ? (string) $item['tax']['taxName'] : null,
                !empty($item['tax']['taxRate']) ? Amount::create((float) $item['tax']['taxRate']) : null,
                !empty($item['tax']['taxApplicationId']) ? (string) $item['tax']['taxApplicationId'] : null,
                !empty($item['tax']['custom']) ? (string) $item['tax']['custom'] : null,
                TaxType::create(!empty($item['tax']['taxType']) ? (string) $item['tax']['taxType'] : null)
            );
        }

        if (!empty($item['rebillAmount']) && !empty($item['rebillDays'])) {
            $rebillTaxBreakdown = null;
            if (!empty($item['tax']) && isset($item['tax']['rebillAmount'])) {
                $rebillTaxBreakdown = TaxBreakdown::create(
                    Amount::create((float) $item['tax']['rebillAmount']['beforeTaxes']),
                    Amount::create((float) $item['tax']['rebillAmount']['taxes']),
                    Amount::create((float) $item['tax']['rebillAmount']['afterTaxes'])
                );
            }

            $bundleChargeInformation = BundleRebillChargeInformation::create(
                Amount::create((float) $item['amount']),
                Duration::create((int) $item['initialDays']),
                $initialTaxBreakdown,
                Amount::create((float) $item['rebillAmount']),
                Duration::create((int) $item['rebillDays']),
                $rebillTaxBreakdown
            );
        } else {
            $bundleChargeInformation = BundleSingleChargeInformation::create(
                Amount::create((float) $item['amount']),
                Duration::create((int) $item['initialDays']),
                $initialTaxBreakdown
            );
        }

        return [
            'bundleChargeInformation' => $bundleChargeInformation,
            'taxInformation'          => $taxInformation,
            'isCrossSale'             => $isCrossSale,
        ];
    }

    /**
     * @param array $item        The item array
     * @param bool  $isCrossSale Is Cross sale flag
     * @return void
     * @throws ItemCouldNotBeRestoredException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function restoreItem(array $item, bool $isCrossSale = false): void
    {
        Log::info('RestoreItem Restoring item', ['item' => $item]);

        try {
            $properties = $this->buildItemProperties($item, $isCrossSale);

            // @todo:BG-52241
            // Standardize flag isNsfSupported and add isNsfSupported flag to proper restore the items
            $restoredItem = InitializedItem::restore(
                ItemId::createFromString($item['itemId']),
                SiteId::createFromString($item['siteId']),
                BundleId::createFromString($item['bundleId']),
                AddonId::createFromString($item['addonId']),
                $properties['bundleChargeInformation'],
                $properties['taxInformation'],
                $properties['isCrossSale'],
                $item['isTrial'] ?? false,
                $item['subscriptionId'] ?? null,
                $item['isCrossSaleSelected']
            );

            $this->initializedItemCollection->offsetSet((string) $restoredItem->itemId(), $restoredItem);

            $this->addTransactionCollectionToItem($item);
        } catch (Throwable $e) {
            throw new ItemCouldNotBeRestoredException($item['itemId'] ?? 'Missing item id', $e);
        }
    }

    /**
     * @param array $item The item array
     * @return void
     * @throws \Exception
     */
    protected function addTransactionCollectionToItem(array $item): void
    {
        if (empty($item['transactionCollection'])) {
            return;
        }

        foreach ($item['transactionCollection'] as $transaction) {
            $transactionId = null;
            if (!empty($transaction['transactionId'])) {
                $transactionId = TransactionId::createFromString($transaction['transactionId']);
            }

            $restoredTransaction = Transaction::create(
                $transactionId,
                $transaction['state'],
                $transaction['billerName'],
                $transaction['newCCUsed'],
                $transaction['acs'],
                $transaction['pareq'],
                $transaction['redirectUrl'],
                $transaction['isNsf'],
                $transaction['deviceCollectionUrl'],
                $transaction['deviceCollectionJwt']
            );
            $restoredTransaction->setDeviceFingerprintId($transaction['deviceFingerprintId']);
            $restoredTransaction->setThreeDStepUpUrl($transaction['threeDStepUpUrl']);
            $restoredTransaction->setThreeDStepUpJwt($transaction['threeDStepUpJwt']);
            $restoredTransaction->setMd($transaction['md']);
            $restoredTransaction->setThreeDFrictionless($transaction['threeDFrictionless']);
            $restoredTransaction->setThreeDVersion($transaction['threeDVersion']);

            if (array_key_exists('errorClassification', $transaction)) {
                $groupDecline      = !empty($transaction['errorClassification']['groupDecline']) ? $transaction['errorClassification']['groupDecline'] : '';
                $errorType         = !empty($transaction['errorClassification']['errorType']) ? $transaction['errorClassification']['errorType'] : '';
                $groupMessage      = !empty($transaction['errorClassification']['groupMessage']) ? $transaction['errorClassification']['groupMessage'] : '';
                $recommendedAction = !empty($transaction['errorClassification']['recommendedAction']) ? $transaction['errorClassification']['recommendedAction'] : '';

                $errorClassification = new ErrorClassification(
                    $groupDecline,
                    $errorType,
                    $groupMessage,
                    $recommendedAction
                );

                $restoredTransaction->setErrorClassification($errorClassification);
            }

            $this->addTransactionToItem(
                $restoredTransaction,
                $item['itemId']
            );
        }
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
     * @param string $transactionId Transaction id
     * @return bool
     */
    public function checkIfTransactionIdExist(string $transactionId): bool
    {
        /** @var Transaction $transaction */
        foreach ($this->retrieveMainPurchaseItem()->transactionCollection() as $transaction) {
            if ((string) $transaction->transactionId() === $transactionId) {
                return true;
            }
        }

        foreach ($this->retrieveInitializedCrossSales() as $crossSale) {
            /** @var Transaction $crossSaleTransaction */
            foreach ($crossSale->transactionCollection() as $crossSaleTransaction) {
                if ((string) $crossSaleTransaction->transactionId() === $transactionId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $transactionId Transaction id
     * @param string $status        Transaction status
     * @return void
     * @throws \Exception
     */
    public function updateTransactionStateFor(string $transactionId, string $status): void
    {
        /** @var Transaction $transaction */
        foreach ($this->retrieveMainPurchaseItem()->transactionCollection() as $transaction) {
            if ((string) $transaction->transactionId() === $transactionId) {
                $transaction->setState($status);

                return;
            }
        }

        foreach ($this->retrieveInitializedCrossSales() as $crossSale) {
            /** @var Transaction $crossSaleTransaction */
            foreach ($crossSale->transactionCollection() as $crossSaleTransaction) {
                if ((string) $crossSaleTransaction->transactionId() === $transactionId) {
                    $crossSaleTransaction->setState($status);

                    return;
                }
            }
        }
    }

    /**
     * @return string|null
     */
    public function mainPurchaseSubscriptionId(): ?string
    {
        return $this->retrieveMainPurchaseItem()->subscriptionId();
    }

    /**
     * @return bool
     */
    public function wasMainItemPurchaseSuccessful(): bool
    {
        return $this->retrieveMainPurchaseItem()->wasItemPurchaseSuccessful();
    }

    /**
     * @return bool
     */
    public function wasMainItemPurchasePending(): bool
    {
        return $this->retrieveMainPurchaseItem()->wasItemPurchasePending();
    }

    /**
     * @return bool
     */
    public function wasMainItemPurchaseSuccessfulOrPending(): bool
    {
        return $this->retrieveMainPurchaseItem()->wasItemPurchaseSuccessfulOrPending();
    }

    /**
     * @return InitializedItem[]
     */
    public function retrieveInitializedCrossSales(): array
    {
        $crossSales = [];
        /** @var InitializedItem $item */
        foreach ($this->initializedItemCollection as $item) {
            if ($item->isCrossSale()) {
                $crossSales[] = $item;
            }
        }
        return $crossSales;
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
     * @return PaymentTemplate|null
     */
    public function retrieveSelectedPaymentTemplate(): ?PaymentTemplate
    {
        foreach ($this->paymentTemplateCollection()->getValues() as $paymentTemplate) {
            if ($paymentTemplate->isSelected()) {
                return $paymentTemplate;
            }
        }

        return null;
    }

    /**
     * @return PaymentTemplate|null
     */
    public function retrieveLastUsedPaymentTemplate(): ?PaymentTemplate
    {
        if ($this->paymentTemplateCollection() instanceof PaymentTemplateCollection
            && $this->paymentTemplateCollection()->count() > 0
        ) {
            return $this->paymentTemplateCollection()->first();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isExistingMemberPurchase(): bool
    {
        return $this->existingMember;
    }

    /**
     * Creates MemberId using value stored in db or create a new instance
     *
     * @param string|null $memberId Member Id
     * @return MemberId
     * @throws \Exception
     */
    public function buildMemberId(string $memberId = null): MemberId
    {
        if ($memberId !== null) {
            $this->memberId = $memberId;
        }

        if (!$this->memberId()) {
            $this->memberId = (string) MemberId::create();
        }

        if (!$this->isExistingMemberPurchase()) {
            $this->wasMemberIdGenerated = true;
        }

        return MemberId::createFromString($this->memberId());
    }

    /**
     * @return void
     */
    public function resetMemberId(): void
    {
        if ($this->wasMemberIdGenerated() || !$this->isExistingMemberPurchase()) {
            $this->memberId = null;
        }
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
     * @return void
     * @throws InvalidEntrySiteSubscriptionCombinationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validateInitData(): void
    {
        if ($this->isExistingMemberPurchase()) {
            $subscriptions    = array_column($this->initializedItemCollection->toArray(), 'subscriptionId');
            $subscriptionsSet = array_filter($subscriptions);

            if (empty($this->entrySiteId())) {
                if (count($subscriptionsSet) != $this->initializedItemCollection->count()) {
                    throw new InvalidEntrySiteSubscriptionCombinationException();
                }
            } else {
                if (!empty($subscriptionsSet)) {
                    throw new InvalidEntrySiteSubscriptionCombinationException();
                }
            }
        }
    }

    /**
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     */
    public function toArray(): array
    {
        $itemsArray = [];
        if (!is_null($this->initializedItemCollection())) {
            $itemsArray = $this->initializedItemCollection()->toArray();
        }

        $paymentTemplates = null;
        if ($this->paymentTemplateCollection() instanceof PaymentTemplateCollection) {
            $paymentTemplates = $this->paymentTemplateCollection()->toArray();
        }

        $paymentTemplateUsed = null;

        if ($this->paymentInfo() instanceof ExistingPaymentInfo) {
            $paymentTemplateUsed = $this->paymentInfo()->paymentTemplateId();
        }

        return [
            'version'                       => SessionVersionConverter::LATEST_VERSION,
            'atlasFields'                   => $this->atlasFields()->toArray(),
            'cascade'                       => $this->cascade() ? $this->cascade()->toArray() : null,
            'fraudAdvice'                   => $this->fraudAdvice() ? $this->fraudAdvice()->toArray() : null,
            'nuDataSettings'                => $this->nuDataSettings() ? $this->nuDataSettings()->toArray() : null,
            'fraudRecommendationCollection' => $this->fraudRecommendation()
                ? $this->fraudRecommendationCollection()->toArray()
                : null,
            'initializedItemCollection'     => $itemsArray,
            'paymentType'                   => $this->paymentInfo()->paymentType(),
            'publicKeyIndex'                => $this->publicKeyIndex(),
            'sessionId'                     => (string) $this->sessionId(),
            'state'                         => (string) $this->state(),
            'userInfo'                      => $this->userInfo()->toArray(),
            'gatewaySubmitNumber'           => $this->gatewaySubmitNumber(),
            'isExpired'                     => $this->isProcessed(),
            'memberId'                      => $this->memberId(),
            'purchaseId'                    => $this->purchaseId(),
            'entrySiteId'                   => $this->entrySiteId(),
            'paymentTemplateCollection'     => $paymentTemplates,
            'existingMember'                => $this->isExistingMemberPurchase(),
            'currency'                      => (string) $this->currency(),
            'redirectUrl'                   => $this->redirectUrl(),
            'postbackUrl'                   => $this->postbackUrl(),
            'paymentMethod'                 => $this->paymentMethod(),
            'trafficSource'                 => $this->trafficSource(),
            'skipVoid'                      => $this->skipVoid(),
            'paymentTemplateId'             => $paymentTemplateUsed,
            'creditCardWasBlacklisted'      => $this->creditCardWasBlacklisted()
        ];
    }

    /**
     * @return Amount
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     * @throws InvalidAmountException
     */
    public function totalAmount(): Amount
    {
        $amount = Amount::create(0);
        /** @var InitializedItem $item */
        foreach ($this->initializedItemCollection()->getIterator() as $item) {
            $amount = $amount->increment($item->chargeInformation()->initialAmount());
        }
        return $amount;
    }

    /**
     * @return CurrencyCode
     */
    public function currency(): CurrencyCode
    {
        return $this->currency;
    }

    /**
     * @return string|null
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentInfo->paymentMethod();
    }

    /**
     * @return string|null
     */
    public function trafficSource(): ?string
    {
        return $this->trafficSource;
    }

    /**
     * @return void
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function updateProcessStateBasedOnCascadeRemainingBillers(): void
    {
        if (!$this->cascade()->hasSubmitsLeft()) {
            $this->state = $this->state()->noMoreBillersAvailable();
            $this->finishProcessing();
        }
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function finishProcessingOrValidate(): void
    {
        if (!$this->cascade()->hasSubmitsLeft()
            || $this->retrieveMainPurchaseItem()->wasItemPurchaseSuccessful()
        ) {
            $this->finishProcessing();

            return;
        }

        $this->validate();

        $this->updateProcessStateBasedOnCascadeRemainingBillers();
    }


    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function setStateIfThirdParty(): void
    {
        if ($this->cascade->firstBiller() instanceof BillerAvailablePaymentMethods
            || !$this->cascade->firstBiller()->isThirdParty()
        ) {
            return;
        }

        $this->startPending();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function filterBillersIfThreeDSAdvised(): void
    {
        if (!$this->fraudAdvice() instanceof FraudAdvice || !$this->cascade() instanceof Cascade) {
            return;
        }

        // Here we filter out non-3DS billers
        if ($this->fraudAdvice()->isForceThreeD()) {
            $this->cascade()->removeNonThreeDSBillers();
        }
    }

    /**
     * @param string|null $username Username
     * @param string|null $email    Email
     * @return void
     * @return void
     * @throws Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoUsername
     * @throws Exception\InvalidUserInfoEmail
     * @throws Exception\InvalidUserInfoPassword
     */
    public function generateOrUpdateUser(?string $username = null, ?string $email = null): void
    {
        if ((!$this->wasMemberIdGenerated() && empty($username))
            || $this->userInfo()->username()
        ) {
            return;
        }

        if (!empty($email)) {
            $this->userInfo()->setEmail(Email::create($email));
        }

        $this->userInfo()->setUsername(Username::create($username ?? Str::random()));

        if (!$this->userInfo()->password()) {
            $this->userInfo()->setPassword(Password::create(Str::random()));
        }
    }

    /**
     * @param string      $transactionId Transaction id
     * @param string      $status        Purchase status
     * @param string      $paymentType   Payment type
     * @param null|string $paymentMethod Payment method
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     * @throws Exception\UnsupportedPaymentTypeException
     */
    public function returnFromThirdPartyUpdates(
        string $transactionId,
        string $status,
        string $paymentType,
        ?string $paymentMethod
    ): void {
        // update transaction status from purchase process(session)
        $this->updateTransactionStateFor($transactionId, $status);

        // update session payment type
        if (!empty($paymentType)) {
            $paymentInfo       = $this->paymentInfo();
            $paymentTemplateId = null;
            $cardHash          = null;

            if ($paymentInfo instanceof ExistingPaymentInfo) {
                $paymentTemplateId = $paymentInfo->paymentTemplateId();
                $cardHash          = $paymentInfo->cardHash();
            }

            $this->setPaymentInfo(
                PaymentInfoFactoryService::create(
                    $paymentType,
                    $paymentMethod,
                    $cardHash,
                    $paymentTemplateId
                )
            );
        }
    }

    /**
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function updateStateForFailedReturnFlow(): void
    {
        if ($this->isValid()
            && !$this->retrieveMainPurchaseItem()->wasItemPurchaseSuccessful()
            && $this->cascade()->isTheNextBillerThirdParty()
        ) {
            $this->startPending();
        }
    }

    /**
     * @return bool
     */
    public function isCurrentBillerAvailablePaymentsMethods(): bool
    {
        return $this->cascade()->currentBiller() instanceof BillerAvailablePaymentMethods;
    }
}
