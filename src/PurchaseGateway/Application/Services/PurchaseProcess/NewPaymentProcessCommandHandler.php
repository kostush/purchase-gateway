<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\BI\NuDataScoreRetrieved;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\RetrieveNuDataScoreException;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataAccountInfoData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCard;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCrossSales;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataEnvironmentData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataPurchasedProduct;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataScoreRequestInfo;
use ProBillerNG\PurchaseGateway\Application\Services\ManageCreditCardBlacklistTrait;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerForCurrentSubmit;
use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\DuplicatedPurchaseProcessRequestException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NonPCIPaymentFormData;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use Ramsey\Uuid\Uuid;
use Throwable;

class NewPaymentProcessCommandHandler extends BasePaymentProcessCommandHandler
{
    use RedisHelperTrait, ManageCreditCardBlacklistTrait;

    /**
     * @var NuDataService
     */
    private $nuDataService;

    /**
     * @var InMemoryRepository
     */
    private $redisRepository;

    /**
     * @var FraudRecommendationServiceFactory
     */
    private $fraudRecommendationServiceFactory;

    /**
     * NewPaymentProcessCommandHandler constructor.
     *
     * @param FraudService                    $fraudService             Fraud Service.
     * @param NuDataService                   $nuDataService            NuData Service.
     * @param BillerMappingService            $billerMappingService     BillerMappingRetrievalOnPurchaseProcess
     * @param LaravelBinRoutingServiceFactory $binRoutingServiceFactory BinRoutingCodesRetrieval
     * @param CascadeTranslatingService       $retrieveCascade          RetrieveCascade
     * @param SessionHandler                    $purchaseProcessHandler   SessionHandler
     * @param PurchaseService                   $purchaseService          PurchaseService
     * @param ProcessPurchaseDTOAssembler       $dtoAssembler             ProcessPurchaseDTOAssembler
     * @param SiteRepositoryReadOnly            $siteRepository           SiteRepositoryReadOnly
     * @param PostbackService                   $postbackService          PostbackService
     * @param BILoggerService                   $biLoggerService          BILoggerService
     * @param TransactionService                $transactionService       The transaction translating service
     * @param EventIngestionService             $eventIngestionService    Event ingestion service
     * @param InMemoryRepository                $redisRepository          The Redis interface
     * @param CCForBlackListService             $CCForBlackListService    CC For BlackList Service
     * @param FraudRecommendationServiceFactory $fraudRecommendationServiceFactory
     */
    public function __construct(
        FraudService $fraudService,
        NuDataService $nuDataService,
        BillerMappingService $billerMappingService,
        LaravelBinRoutingServiceFactory $binRoutingServiceFactory,
        CascadeTranslatingService $retrieveCascade,
        SessionHandler $purchaseProcessHandler,
        PurchaseService $purchaseService,
        ProcessPurchaseDTOAssembler $dtoAssembler,
        SiteRepositoryReadOnly $siteRepository,
        PostbackService $postbackService,
        BILoggerService $biLoggerService,
        TransactionService $transactionService,
        EventIngestionService $eventIngestionService,
        InMemoryRepository $redisRepository,
        CCForBlackListService $CCForBlackListService,
        FraudRecommendationServiceFactory $fraudRecommendationServiceFactory
    ) {
        parent::__construct(
            $fraudService,
            $billerMappingService,
            $binRoutingServiceFactory,
            $retrieveCascade,
            $purchaseProcessHandler,
            $purchaseService,
            $dtoAssembler,
            $siteRepository,
            $postbackService,
            $biLoggerService,
            $transactionService,
            $eventIngestionService
        );

        $this->nuDataService                     = $nuDataService;
        $this->redisRepository                   = $redisRepository;
        $this->fraudRecommendationServiceFactory = $fraudRecommendationServiceFactory;

        $this->init($CCForBlackListService);
    }

    /**
     * @param Command $command The command
     *
     * @return mixed|ProcessPurchaseHttpDTO
     *
     * @throws DuplicatedPurchaseProcessRequestException
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidCommandException
     * @throws InvalidPaymentInfoException
     * @throws LoggerException
     * @throws Throwable
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof ProcessPurchaseCommand) {
            throw new InvalidCommandException(ProcessPurchaseCommand::class, $command);
        }

        // Call the helper for redis which will validate based on the sessionId:
        //   - If the key exists and the value is "processing", then throw "DuplicatedPurchaseProcessRequestException"
        //   - If the key does not exist, then store the key and continue the flow
        $this->isProcessAlreadyStarted($command->sessionId());

        // Temporary solution to avoid moving forward if the credit card is not accepted
        if (env('APP_ENV') !== 'local'
            && !$this->acceptedCard($command->ccNumber(), (string) $command->site()->siteId())) {
            throw new InvalidPaymentInfoException('Payment method not supported.');
        }

        // retrieve purchase process
        $this->purchaseProcess = $this->purchaseProcessHandler->load($command->sessionId());

        // throw exception if the process already started with third party biller
        $this->purchaseProcess->wasStartedWithThirdPartyBiller();

        // Get fraud service status for the given site
        $isFraudServiceEnabled = $this->shouldSetFraudAdvice($command->site());

        try {
            // Check if purchase has been already processes
            $this->checkIfPurchaseHasBeenAlreadyProcessed();

            $this->checkIfTheMaximumAttemptsUsingABlacklistedCardWasReached();

            // Check CC For Blacklist and send BI event
            if (!empty($command->ccNumber())) {
                $this->sendBiEventIfCreditCardIsBlacklisted(
                    substr($command->ccNumber(), 0, 6),
                    substr($command->ccNumber(), -4),
                    (string) ((int) $command->expirationMonth()),
                    $command->expirationYear(),
                    $command->sessionId(),
                    $command->email(),
                    (string) $this->purchaseProcess->totalAmount()->value(),
                    $this->purchaseProcess->memberId()
                );
            }

            if ($isFraudServiceEnabled) {
                // If fraud is enabled check if the process needs to be stopped due to fraud
                $this->checkIfPurchaseCanBeProcessedDueToFraud();

                // Check user input & update fraud advice if necessary
                $this->buildCheckUserInput($command);

                // BG-38099: If fraud is enabled and the user is fraudulent, we need to stop the process right away
                if ($this->purchaseProcess->isFraud()) {
                    $this->purchaseProcess->blockDueToFraudAdvice();

                    return $this->dtoAssembler->assemble($this->purchaseProcess);
                }
            } else {
                // BG-38099: skip fraud check is the service is disabled for the given site
                Log::info(
                    'Fraud is disabled for this site, skipping retrieval of advice',
                    ['siteId' => $command->site()->siteId()]
                );
            }

            $this->addUserInfoToPurchaseProcess($command);

            $this->purchaseProcess->filterBillersIfThreeDSAdvised();

            $this->purchaseProcess->wasThreeDStarted();

            $this->purchaseProcess->validate();

            $this->purchaseProcess->startProcessing();

            $this->setPaymentInfo($command);

            $billerForNextTransactionAttempts = BillerForCurrentSubmit::create(
                $this->purchaseProcess->cascade(),
                null
            );

            $billerMapping = $this->retrieveBillerMapping(
                $command->site(),
                $billerForNextTransactionAttempts->biller()
            );

            // BG-42324 - C4S is hitting several banks on every Submit.
            // This is problematic in cases of declines as multiple flags can be raised and
            // can also negatively impact the transaction approval ratio.
            // To mitigate this, the team proposed to integrate the sticky gateway mid feature from rocketgate.
            // The Customer will always go through his approved MID thereafter.
            // When the Sticky MIDs option is enabled,
            // All rebills are automatically sent to a subscription's original MID
            if (RocketgateBiller::BILLER_NAME === $billerForNextTransactionAttempts->biller()->name()
                && $command->site()->isStickyGateway()
            ) {
                if ($billerMapping->billerFields() instanceof RocketgateBillerFields) {
                    $billerMapping->billerFields()->setMerchantCustomerId(md5($command->email()));
                }
            }

            $binRoutingCodes = new BinRoutingCollection();
            if (!$this->purchaseProcess->paymentInfo() instanceof ChequePaymentInfo) {
                $binRoutingCodes = $this->retrieveRoutingCodes(
                    $command->site(),
                    $billerMapping
                );
            }
            $this->checkSelectedCrossSales($command);

            // Add isNFSSupported value for each site. We can have different sites for main purchase and xSell.
            // Therefore, it is important to add this value for each one of the sites.
            $this->setIsNSFValueForEachInitializedItem($command);

            $this->transactionService->attemptTransactions(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                $billerMapping,
                $binRoutingCodes,
                $billerForNextTransactionAttempts->biller(),
                AttemptTransactionData::create(
                    $this->purchaseProcess->currency(),
                    $this->purchaseProcess->userInfo(),
                    $this->purchaseProcess->paymentInfo()
                ),
                $this->purchaseProcess->fraudAdvice(),
                $command->site()
            );

            // Call config service for a possible CC blacklist
            $creditCardWasBlacklisted = $this->blacklistCreditCardIfNeeded(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                substr($command->ccNumber(), 0, 6),
                substr($command->ccNumber(), -4),
                (string) ((int) $command->expirationMonth()),
                $command->expirationYear(),
                (string) $this->purchaseProcess->sessionId()
            );

            $this->purchaseProcess->setCreditCardWasBlacklisted($creditCardWasBlacklisted);

            $this->purchaseProcess->postProcessing();

            //This check is done here so that we don't block the process if we don't receive a redirect url,
            // 3DS flow is triggered but the card is not enrolled
            $this->checkReturnUrl();

            $this->purchase = $this->purchaseService->createPurchaseEntity(
                $this->purchaseProcess,
                $command->site()
            );

            $dto = $this->dtoAssembler->assemble($this->purchaseProcess);

            if ($this->purchaseProcess->isProcessed()) {
                $this->postbackService->queue(
                    $this->buildDtoPostback($dto),
                    $this->getPostbackUrl($command->site())
                );
            }

            // NuData score
            $this->purchaseNuDataScore($command);

            // Ship BI event
            $this->shipBiProcessedPurchaseEvent($command->site());

            if ($this->purchaseProcess->isValid() && $this->purchaseProcess->cascade()->isTheNextBillerThirdParty()) {
                $this->purchaseProcess->startPendingThirdPartyProcess();

                $this->shipBiProcessedPurchaseEvent($command->site());
            }

            // Return DTO
            return $dto;
        } catch (Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::logException($e);
            throw $e;
        } finally {
            $this->purchaseProcess->incrementGatewaySubmitNumber();

            // Store the purchase process
            $this->purchaseProcessHandler->update($this->purchaseProcess);

            // Remove the redis key as the process has finished and all data have been stored
            $this->removeKeyOfFinishedProcess($command->sessionId());
        }
    }

    /**
     * @param ProcessPurchaseCommand $command The purchase command
     *
     * @return void
     * @throws LoggerException
     * @throws \Throwable
     */
    protected function addUserInfoToPurchaseProcess(ProcessPurchaseCommand $command): void
    {
        try {
            if (empty($command->country())) {
                throw new InvalidUserInfoCountry();
            }

            $this->purchaseProcess->userInfo()->setCountryCode(CountryCode::create($command->country()));
        } catch (\Throwable $e) {
            throw $e;
        }

        parent::addUserInfoToPurchaseProcess($command);
    }

    /**
     * @param ProcessPurchaseCommand $command Command
     *
     * @return void
     * @throws LoggerException
     * @throws InvalidUserInfoEmail
     * @throws ValidationException
     */
    protected function buildCheckUserInput(ProcessPurchaseCommand $command): void
    {
        if (config('app.feature.common_fraud_enable_for.process.new_credit_card')) {
            $this->checkFraudAdviceOnCommonService($command);

            return;
        }

        $email  = !empty($command->email()) ? Email::create($command->email()) : null;
        $bin    = !empty($command->ccNumber()) ? Bin::createFromCCNumber($command->ccNumber()) : null;
        $zip    = !empty($command->zip()) ? Zip::create($command->zip()) : null;
        $siteId = $command->site()->siteId();

        $this->checkUserInput($email, $bin, $zip, $siteId);
    }

    /**
     * @param ProcessPurchaseCommand $command The process command
     *
     * @return void
     * @throws LoggerException
     * @throws Throwable
     * @throws InvalidCreditCardExpirationDate
     */
    protected function setPaymentInfo(ProcessPurchaseCommand $command): void
    {
        $paymentInfo = null;
        if ($command->ccNumber()) {
            Log::info('Setting the payment info type cc');

            $paymentInfo = NewCCPaymentInfo::create(
                $command->ccNumber(),
                $command->cvv(),
                $command->expirationMonth(),
                $command->expirationYear(),
                $this->purchaseProcess->paymentMethod()
            );
        }

        if (!empty($command->routingNumber())
            && !empty($command->accountNumber())
            && !empty($command->socialSecurityLast4())
        ) {
            Log::info('Setting the payment info type checks');

            $paymentInfo = ChequePaymentInfo::create(
                $command->routingNumber(),
                $command->accountNumber(),
                $command->savingAccount(),
                $command->socialSecurityLast4(),
                $command->paymentMethod() ?? $this->purchaseProcess->paymentInfo()->paymentType(),
                $this->purchaseProcess->paymentMethod()
            );
        }

        if (empty($paymentInfo)) {
            throw new InvalidPaymentInfoException("");
        }

        $this->purchaseProcess->setPaymentInfo($paymentInfo);
    }

    /**
     * @param Site   $site   Site
     * @param Biller $biller Biller
     *
     * @return BillerMapping
     * @throws BillerMappingException
     * @throws LoggerException
     */
    protected function retrieveBillerMapping(Site $site, Biller $biller): BillerMapping
    {
        try {
            return $this->billerMappingService->retrieveBillerMapping(
                (string) $biller,
                (string) $site->businessGroupId(),
                (string) $site->siteId(),
                (string) $this->purchaseProcess->currency(),
                (string) $this->purchaseProcess->sessionId()
            );
        } catch (\Exception $e) {
            Log::info('Unable to retrieve biller fields');
            throw new BillerMappingException($e);
        }
    }

    /**
     * @return BaseEvent
     * @throws ValidationException
     * @throws LoggerException
     * @throws \Exception
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        $paymentInfo = $this->purchaseProcess->paymentInfo();
        $payment     = null;

        if ($paymentInfo instanceof ChequePaymentInfo) {
            return PurchaseBiEventFactory::createForCheck($this->purchaseProcess);
        }

        return PurchaseBiEventFactory::createForNewCC($this->purchaseProcess);
    }

    /**
     * @param ProcessPurchaseCommand $command Command
     *
     * @return void
     * @throws LoggerException
     * @throws ValidationException
     */
    private function checkFraudAdviceOnCommonService(ProcessPurchaseCommand $command): void
    {
        $nonPCIPaymentFormData      = $this->generateNonPCIDataFromCommand(
            $command,
            $this->purchaseProcess->userInfo()
        );
        $fraudRecommendationService = $this->fraudRecommendationServiceFactory->buildFraudRecommendationForPaymentOnProcess(
            $this->purchaseProcess->paymentInfo()->paymentType()
        );

        try {
            $processFraudRecommendation = $fraudRecommendationService->retrieve(
                $command->site()->businessGroupId(),
                $command->site()->siteId(),
                $nonPCIPaymentFormData,
                $this->purchaseProcess->totalAmount(),
                $this->purchaseProcess->sessionId(),
                $command->fraudHeaders()
            );

            $this->purchaseProcess->setFraudRecommendationCollection($processFraudRecommendation);
            $this->purchaseProcess->setFraudAdvice(
                FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
                    $processFraudRecommendation,
                    $this->purchaseProcess->fraudAdvice()
                )
            );
        } catch (\Exception $e) {
            // Unable to check fraud for user, continue
            Log::info('Fraud check on user input failed');
            Log::logException($e);
        }
    }

    /**
     * @param ProcessPurchaseCommand $processPurchaseCommand Process Purchase Command
     *
     * @return NuDataScoreRequestInfo
     */
    private function retrieveNuDataScoreRequestInfo(
        ProcessPurchaseCommand $processPurchaseCommand
    ): NuDataScoreRequestInfo {
        $nuDataEnvironmentData = new NuDataEnvironmentData(
            (string) $this->purchaseProcess->sessionId(),
            $processPurchaseCommand->ndWidgetData(),
            (string) $this->purchaseProcess->userInfo()->ipAddress(),
            $processPurchaseCommand->site()->url(),
            $processPurchaseCommand->userAgent(),
            $processPurchaseCommand->xForwardedFor()
        );

        /** @var InitializedItem $mainPurchaseItem */
        $mainPurchaseItem = $this->purchaseProcess->retrieveMainPurchaseItem();

        $nuDataPurchaseProduct = new NuDataPurchasedProduct(
            $mainPurchaseItem->chargeInformation()->initialAmount()->value(),
            (string) $mainPurchaseItem->bundleId(),
            $mainPurchaseItem->wasItemPurchaseSuccessful(),
            $mainPurchaseItem->subscriptionId(),
            $mainPurchaseItem->isTrial(),
            $mainPurchaseItem->chargeInformation() instanceof BundleRebillChargeInformation ? true : false
        );

        $nuDataCard = new NuDataCard(
            $processPurchaseCommand->firstName() . " " . $processPurchaseCommand->lastName(),
            $processPurchaseCommand->ccNumber()
        );

        $nuDataAccountInfoData = new NuDataAccountInfoData(
            $processPurchaseCommand->username(),
            $processPurchaseCommand->password(),
            $processPurchaseCommand->email(),
            $processPurchaseCommand->firstName(),
            $processPurchaseCommand->lastName(),
            $processPurchaseCommand->phoneNumber(),
            $processPurchaseCommand->address(),
            $processPurchaseCommand->city(),
            $processPurchaseCommand->state(),
            $processPurchaseCommand->country(),
            $processPurchaseCommand->zip()
        );

        /** @var NuDataCrossSales $nuDataCrossSales */
        $nuDataCrossSales = $this->generateNuDataCrossSales();

        $nuDataScoreRequestInfo = new NuDataScoreRequestInfo(
            (string) $processPurchaseCommand->site()->businessGroupId(),
            $nuDataEnvironmentData,
            $nuDataPurchaseProduct,
            $nuDataCard,
            $nuDataAccountInfoData,
            $nuDataCrossSales
        );

        return $nuDataScoreRequestInfo;
    }

    /**
     * @return NuDataCrossSales
     */
    private function generateNuDataCrossSales(): NuDataCrossSales
    {
        $nuDataCrossSales = new NuDataCrossSales();
        /** @var InitializedItem[] $processedCrossSales */
        $processedCrossSales = $this->purchaseProcess->retrieveProcessedCrossSales();

        foreach ($processedCrossSales as $crossSale) {
            $purchasedCrossSale = new NuDataPurchasedProduct(
                $crossSale->chargeInformation()->initialAmount()->value(),
                (string) $crossSale->bundleId(),
                $crossSale->wasItemPurchaseSuccessful(),
                $crossSale->subscriptionId(),
                $crossSale->isTrial(),
                $crossSale->chargeInformation() instanceof BundleRebillChargeInformation ? true : false
            );

            $nuDataCrossSales->addProduct($purchasedCrossSale);
        }

        return $nuDataCrossSales;
    }

    /**
     * @param string $businessGroupId Business Group Id
     * @param string $nuDataScore     NuData Score
     *
     * @return NuDataScoreRetrieved
     * @throws \Exception
     */
    private function generateBINuDataScoreRetrieved(string $businessGroupId, string $nuDataScore): NuDataScoreRetrieved
    {
        /** @var InitializedItem $mainPurchaseItem */
        $mainPurchaseItem = $this->purchaseProcess->retrieveMainPurchaseItem();
        /** @var InitializedItem[] $processedCrossSales */
        $processedCrossSales = $this->purchaseProcess->retrieveProcessedCrossSales();

        $nuDataScoreRetrieved = new NuDataScoreRetrieved(
            (string) Uuid::uuid4(),
            (string) $this->purchaseProcess->sessionId(),
            (string) $this->purchaseProcess->sessionId(),
            $this->purchaseProcess->purchase(),
            $mainPurchaseItem,
            $processedCrossSales,
            $businessGroupId,
            $nuDataScore
        );

        return $nuDataScoreRetrieved;
    }

    /**
     * @param ProcessPurchaseCommand $processPurchaseCommand Process Purchase Command
     *
     * @return void
     * @throws LoggerException
     * @throws \Exception
     */
    private function purchaseNuDataScore(ProcessPurchaseCommand $processPurchaseCommand): void
    {
        try {
            /** @var NuDataSettings $nuDataSettings */
            $nuDataSettings = $this->purchaseProcess->nuDataSettings();

            if (!$nuDataSettings || !$nuDataSettings->enabled() || !$processPurchaseCommand->ndWidgetData()) {
                return;
            }

            /** @var NuDataScoreRequestInfo $retrieveNuDataScoreRequestInfo */
            $retrieveNuDataScoreRequestInfo = $this->retrieveNuDataScoreRequestInfo($processPurchaseCommand);

            Log::info(
                'Retrieve NuData score for session "' .
                $this->purchaseProcess->sessionId() . '" with params: ',
                $retrieveNuDataScoreRequestInfo->toArray()
            );

            /** @var string $nuDataScore */
            $nuDataScore = $this->nuDataService->retrieveScore($retrieveNuDataScoreRequestInfo);

            Log::info(
                'NuData score retrieved for session "' .
                $this->purchaseProcess->sessionId() . '": ' . $nuDataScore
            );

            /** @var NuDataScoreRetrieved $nuDataScoreRetrieved */
            $nuDataScoreRetrieved = $this->generateBINuDataScoreRetrieved(
                (string) $processPurchaseCommand->site()->businessGroupId(),
                $nuDataScore
            );

            //NuData Score BI event
            $this->biLoggerService->write($nuDataScoreRetrieved);
            if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
                $this->eventIngestionService->queue($nuDataScoreRetrieved);
            }
        } catch (RetrieveNuDataScoreException $exception) {
            //in case of exception the purchase have not be interrupted
            Log::logException($exception);
        }
    }

    /**
     * @return void
     * @throws MissingRedirectUrlException
     */
    protected function checkReturnUrl(): void
    {
        // required only when state is pending (3DS started)
        if ($this->purchaseProcess->isPending() && empty($this->purchaseProcess->redirectUrl())) {
            throw new MissingRedirectUrlException(
                RestartProcess::create()->toArray()
            );
        }
    }

    /**
     * @param ProcessPurchaseCommand $command  Command
     * @param UserInfo               $userInfo User info
     *
     * @return NonPCIPaymentFormData
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws LoggerException
     */
    public function generateNonPCIDataFromCommand(
        ProcessPurchaseCommand $command,
        UserInfo $userInfo
    ): NonPCIPaymentFormData {
        $email = !empty($command->email()) ? Email::create($command->email()) : $userInfo->email() ?? null;

        $zipCode = !empty($command->zip()) ? Zip::create($command->zip()) : null;

        $countryCode = !empty($command->country()) ? CountryCode::create($command->country()) : null;

        $lastName = !empty($command->lastName()) ? LastName::create($command->lastName()) : $userInfo->lastName() ?? null;

        $firstName = !empty($command->firstName()) ? FirstName::create($command->firstName()) : $userInfo->firstName() ?? null;

        $bin = !empty($command->ccNumber()) ? Bin::createFromCCNumber($command->ccNumber()) : null;

        $lastFour = !empty($command->ccNumber()) ? LastFour::createFromCCNumber($command->ccNumber()) : null;

        $routingNumber = !empty($command->routingNumber()) ? $command->routingNumber() : null;

        return NonPCIPaymentFormData::createForProcessCustomer(
            $bin,
            $lastFour,
            $email,
            $zipCode,
            $countryCode,
            $firstName,
            $lastName,
            $routingNumber
        );
    }

    /**
     * https://wiki.mgcorp.co/display/PROBILLER/Purchase+Gateway+support+for+NSF
     * Based on this documentation:
     * The feature needs to be enabled for the entry site.
     * When enabled, NSF flow will apply to the main purchase and to the cross sale.
     *
     * The cross sale will always follow the main site setup.
     *
     * @param ProcessPurchaseCommand $command The process command
     *
     * @return void
     * @throws \Exception
     */
    protected function setIsNSFValueForEachInitializedItem(ProcessPurchaseCommand $command): void
    {
        /** @var $item InitializedItem */
        foreach ($this->purchaseProcess->initializedItemCollection() as $item) {
            $item->setIsNSFSupported($command->site()->isNsfSupported());
        }
    }
}
