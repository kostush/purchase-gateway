<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use Carbon\Carbon;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\BI\FraudFailedPaymentTemplateValidation;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\ManageCreditCardBlacklistTrait;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerForCurrentSubmit;
use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\DuplicatedPurchaseProcessRequestException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\PaymentTemplateValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingCardOnProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;

class ExistingPaymentProcessCommandHandler extends BasePaymentProcessCommandHandler
{
    use RedisHelperTrait, ManageCreditCardBlacklistTrait;

    /**
     * @var PaymentTemplateService
     */
    protected $paymentTemplateService;

    /**
     * @var RetrieveFraudRecommendationForExistingCardOnProcess
     */
    private $fraudRecommendationForExistingCardOnProcess;

    /**
     * @var MemberProfileGatewayService
     */
    private $memberProfileGatewayService;

    /**
     * @var InMemoryRepository
     */
    private $redisRepository;

    /**
     * ExistingPaymentProcessCommandHandler constructor.
     * @param FraudService                                        $fraudService                                Fraud Service.
     * @param BillerMappingService                                $billerMappingService                        BillerMappingRetrievalOnPurchaseProcess
     * @param LaravelBinRoutingServiceFactory                     $binRoutingServiceFactory                    BinRoutingCodesRetrieval
     * @param CascadeTranslatingService                           $retrieveCascade                             RetrieveCascade
     * @param SessionHandler                                      $purchaseProcessHandler                      SessionHandler
     * @param PurchaseService                                     $purchaseService                             PurchaseService
     * @param ProcessPurchaseDTOAssembler                         $dtoAssembler                                ProcessPurchaseDTOAssembler
     * @param SiteRepositoryReadOnly                              $siteRepository                              SiteRepositoryReadOnly
     * @param PostbackService                                     $postbackService                             PostbackService
     * @param BILoggerService                                     $biLoggerService                             BILoggerService
     * @param TransactionService                                  $transactionService                          The transaction translating service
     * @param PaymentTemplateService                              $paymentTemplateService                      Payment template service
     * @param RetrieveFraudRecommendationForExistingCardOnProcess $fraudRecommendationForExistingCardOnProcess Fraud recommendation Interface
     * @param MemberProfileGatewayService                         $memberProfileGatewayService                 Member Profile Gateway
     * @param EventIngestionService                               $eventIngestionService                       Event ingestion service
     * @param InMemoryRepository                                  $redisRepository                             The Redis interface
     * @param CCForBlackListService                               $CCForBlackListService                       CC For BlackList Service
     */
    public function __construct(
        FraudService $fraudService,
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
        PaymentTemplateService $paymentTemplateService,
        RetrieveFraudRecommendationForExistingCardOnProcess $fraudRecommendationForExistingCardOnProcess,
        MemberProfileGatewayService $memberProfileGatewayService,
        EventIngestionService $eventIngestionService,
        InMemoryRepository $redisRepository,
        CCForBlackListService $CCForBlackListService
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

        $this->paymentTemplateService                      = $paymentTemplateService;
        $this->fraudRecommendationForExistingCardOnProcess = $fraudRecommendationForExistingCardOnProcess;
        $this->memberProfileGatewayService                 = $memberProfileGatewayService;
        $this->redisRepository                             = $redisRepository;

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
     * @throws PaymentTemplateValidationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \Throwable
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

        // retrieve purchase process
        $this->purchaseProcess = $this->purchaseProcessHandler->load($command->sessionId());

        //throw exception if the process already started with third party biller
        //(this have to be uncommented when the sec rev with third party will be implemented)
        //$this->purchaseProcess->wasStartedWithThirdPartyBiller();

        // Get fraud service status for the given site
        $isFraudServiceEnabled = $this->shouldSetFraudAdvice($command->site());

        try {
            // Check if purchase has been already processes
            $this->checkIfPurchaseHasBeenAlreadyProcessed();

            $this->checkIfTheMaximumAttemptsUsingABlacklistedCardWasReached();

            $paymentTemplate = $this->paymentTemplateService->retrievePaymentTemplate(
                $this->purchaseProcess,
                [
                    'paymentTemplateId' => $command->paymentTemplateId(),
                    'lastFour'          => $command->lastFour()
                ]
            );

            // Temporary solution to avoid moving forward if the credit card is not accepted
            if (!env('TEST_ENV', false)
                && !$this->acceptedCard($command->ccNumber(), (string) $command->site()->siteId())
            ) {
                throw new InvalidPaymentInfoException('Payment method not supported.');
            }

            $this->sendBiEventIfCreditCardIsBlacklisted(
                $paymentTemplate->firstSix(),
                $paymentTemplate->lastFour(),
                $paymentTemplate->expirationMonth(),
                $paymentTemplate->expirationYear(),
                $command->sessionId(),
                !empty($command->email()) ? $command->email() : (string) $this->purchaseProcess->userInfo()->email(),
                (string) $this->purchaseProcess->totalAmount()->value(),
                $this->purchaseProcess->memberId()
            );

            $this->addUserInfoToPurchaseProcess($command);

            if ($isFraudServiceEnabled) {
                // If fraud is enabled check if the process needs to be stopped due to fraud
                $this->checkIfPurchaseCanBeProcessedDueToFraud();

                // Check user input & update fraud advice if necessary
                $this->buildCheckUserInput($command, $paymentTemplate);

                // BG-36911: If fraud is enabled and the user is fraudulent, we need to stop the process right away
                if ($this->purchaseProcess->isFraud()) {
                    $this->purchaseProcess->blockDueToFraudAdvice();

                    return $this->dtoAssembler->assemble($this->purchaseProcess);
                }
            } else {
                // BG-36911: skip fraud check is the service is disabled for the given site
                Log::info(
                    'Fraud is disabled for this site, skipping retrieval of advice',
                    ['siteId' => $command->site()->siteId()]
                );
            }

            $this->purchaseProcess->validate();

            $this->purchaseProcess->startProcessing();

            $this->setPaymentInfo();

            $billerForNextTransactionAttempts = BillerForCurrentSubmit::create(
                $this->purchaseProcess->cascade(),
                $this->purchaseProcess->retrieveSelectedPaymentTemplate()
            );

            $billerMapping = $this->retrieveBillerMapping(
                $command->site(),
                $billerForNextTransactionAttempts->biller()
            );

            $this->checkSelectedCrossSales($command);

            // BG-47057: For secondary revenue we should always make the transaction with the same bank.
            // Therefore, the call to bin routing should not be made and instead we send an empty collection.
            $this->transactionService->attemptTransactions(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                $billerMapping,
                new BinRoutingCollection(),
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
                $paymentTemplate->firstSix(),
                $paymentTemplate->lastFour(),
                (string) ((int) $paymentTemplate->expirationMonth()),
                $paymentTemplate->expirationYear(),
                (string) $this->purchaseProcess->sessionId()
            );

            $this->purchaseProcess->setCreditCardWasBlacklisted($creditCardWasBlacklisted);

            $this->purchaseProcess->postProcessing();

            $this->purchase = $this->purchaseService->createPurchaseEntity(
                $this->purchaseProcess,
                $command->site()
            );

            $dto = $this->dtoAssembler->assemble($this->purchaseProcess);

            //(this have to be uncommented when the sec rev with third party will be implemented)
            //$this->purchaseProcess->startPendingIfNextBillerIsThirdParty();

            if ($this->purchaseProcess->isProcessed()) {
                $this->postbackService->queue(
                    $this->buildDtoPostback($dto),
                    $this->getPostbackUrl($command->site())
                );
            }

            // Ship BI event
            $this->shipBiProcessedPurchaseEvent($command->site());

            // Return DTO
            return $dto;
        } catch (RuntimeException $e) {
            // TODO change the way we handle CB issues
            //      so we do not expose infrastructure issues in the application layer
            if (!empty($e->getFallbackException())) {
                throw $e->getFallbackException();
            }

            throw $e;
        } catch (PaymentTemplateValidationException $e) {
            /**
             * Simulate the processing state in order for the
             * purchase process state machine to continue working as expected
             */
            $this->purchaseProcess->startProcessing();

            if ($e instanceof InvalidPaymentTemplateLastFour) {
                $this->shipFailedPaymentTemplateValidationEvent(
                    $command->site()
                );
            }

            throw $e;
        } catch (Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
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
     * @param ProcessPurchaseCommand $command         Process purchase command
     * @param PaymentTemplate        $paymentTemplate Payment template
     *
     * @return void
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function buildCheckUserInput(ProcessPurchaseCommand $command, PaymentTemplate $paymentTemplate): void
    {
        if (config('app.feature.common_fraud_enable_for.process.existing_credit_card')) {
            $this->checkFraudAdviceOnCommonService($command, $paymentTemplate->firstSix(),
                $paymentTemplate->lastFour());
            return;
        }

        $bin    = !empty($command->ccNumber()) ? Bin::createFromCCNumber($command->ccNumber()) : null;
        $siteId = $command->site()->siteId();

        $this->checkUserInput(null, $bin, null, $siteId);
    }

    /**
     * @param ProcessPurchaseCommand $command The purchase command
     *
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    protected function addUserInfoToPurchaseProcess(ProcessPurchaseCommand $command): void
    {
        try {
            // In the case of secondary revenue with subscription id, we will retrieve the member profile
            if (!empty($this->purchaseProcess->mainPurchaseSubscriptionId())) {
                Log::info('AddUserInfo From Member Profile.');

                $retrievedMemberInfo = $this->retrieveMemberInfo(
                    $this->purchaseProcess->memberId(),
                    $command->site(),
                    $this->purchaseProcess->sessionId(),
                    $this->purchaseProcess->mainPurchaseSubscriptionId()
                );
                $this->purchaseProcess->userInfo()->setUsername($retrievedMemberInfo->username());
                $this->purchaseProcess->userInfo()->setEmail($retrievedMemberInfo->email());

                return;
            }

            // In the case of secondary revenue with entry site id, we will use the information from command (request)
            Log::info('AddUserInfo From Request.');
            $this->purchaseProcess->userInfo()->setUsername(Username::create($command->username()));
            $this->purchaseProcess->userInfo()->setPassword(Password::create($command->password()));

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @return void
     * @throws \Throwable
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function setPaymentInfo(): void
    {
        Log::info('Setting the existing cc payment info');

        $selectedPaymentTemplate = $this->purchaseProcess->retrieveSelectedPaymentTemplate();

        $paymentInfo = ExistingCCPaymentInfo::create(
            $selectedPaymentTemplate->billerFields()['cardHash'],
            $selectedPaymentTemplate->templateId(),
            $this->purchaseProcess->paymentMethod(),
            $selectedPaymentTemplate->billerFields()
        );

        $this->purchaseProcess->setPaymentInfo($paymentInfo);
    }

    /**
     * @param Site   $site   Site
     * @param Biller $biller Biller
     * @return BillerMapping
     * @throws BillerMappingException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function retrieveBillerMapping(Site $site, Biller $biller): BillerMapping
    {
        try {
            $billerMapping = $this->billerMappingService->retrieveBillerMapping(
                (string) $biller,
                (string) $site->businessGroupId(),
                (string) $site->siteId(),
                (string) $this->purchaseProcess->currency(),
                (string) $this->purchaseProcess->sessionId()
            );

            if ($billerMapping->billerFields() instanceof RocketgateBillerFields) {
                $billerMapping->billerFields()->setMerchantCustomerId(
                    $this->purchaseProcess->retrieveSelectedPaymentTemplate()
                        ->billerFields()['merchantCustomerId']
                );
            }

            return $billerMapping;
        } catch (\Exception $e) {
            Log::info('Unable to retrieve biller fields');
            throw new BillerMappingException($e);
        }
    }

    /**
     * @return BaseEvent
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     * @throws ValidationException
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        return PurchaseBiEventFactory::createForPaymentTemplate($this->purchaseProcess);
    }

    /**
     * @param ProcessPurchaseCommand $command  Command
     * @param string                 $bin      Bin
     * @param string                 $lastFour Last four
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    private function checkFraudAdviceOnCommonService(
        ProcessPurchaseCommand $command,
        string $bin,
        string $lastFour
    ): void {
        try {
            $email = !empty($command->email())
                ? Email::create($command->email()) : $this->purchaseProcess->userInfo()->email();
            $processFraudRecommendationCollection = $this->fraudRecommendationForExistingCardOnProcess->retrieve(
                $command->site()->businessGroupId(),
                $command->site()->siteId(),
                $this->purchaseProcess->userInfo()->countryCode(),
                $this->purchaseProcess->userInfo()->ipAddress(),
                $email,
                BIN::createFromString($bin),
                LastFour::createFromString($lastFour),
                $this->purchaseProcess->totalAmount(),
                $this->purchaseProcess->sessionId(),
                $command->fraudHeaders()
            );

            $this->purchaseProcess->setFraudRecommendationCollection($processFraudRecommendationCollection);
            $this->purchaseProcess->setFraudAdvice(
                FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnProcess(
                    $processFraudRecommendationCollection,
                    $this->purchaseProcess->fraudAdvice()
                )
            );
        } catch (\Exception $e) {
            $this->purchaseProcess->setFraudRecommendation(FraudRecommendation::createDefaultAdvice());
            $this->purchaseProcess->setFraudAdvice(FraudAdvice::create());
            Log::info('Fraud check on user input failed');
            Log::logException($e);
        }
    }

    /**
     * @param string      $memberId       Member Id
     * @param Site        $site           Site
     * @param SessionId   $sessionId      Session Id
     * @param string|null $subscriptionId Subscription Id
     *
     * @return MemberInfo
     */
    private function retrieveMemberInfo(
        string $memberId,
        Site $site,
        SessionId $sessionId,
        ?string $subscriptionId
    ): MemberInfo {
        return $this->memberProfileGatewayService->retrieveMemberProfile(
            $memberId,
            $site->id(),
            $site->publicKeys()[0],
            (string) $sessionId,
            $subscriptionId
        );
    }

    /**
     * @param Site $site
     *
     * @throws \ProBillerNG\Logger\Exception
     */
    private function shipFailedPaymentTemplateValidationEvent(Site $site): void
    {
        Log::info("Last4FraudEvent Shipping and creating failed payment template validation event");

        $memberInfo     = $this->retrieveMemberInfo(
            $this->purchaseProcess->memberId(),
            $site,
            $this->purchaseProcess->sessionId(),
            $this->purchaseProcess->mainPurchaseSubscriptionId()
        );
        $timestamp       = Carbon::now()->toISOString();
        $siteId          = $site->siteId();
        $businessGroupId = $site->businessGroupId();

        try {
            $event = new FraudFailedPaymentTemplateValidation(
                (string) $memberInfo->email(),
                (string) $timestamp,
                (string) $siteId,
                (string) $businessGroupId
            );

            $this->eventIngestionService->queue($event);
        } catch (\Throwable $exception) {
            Log::warning(
                "Last4FraudEvent Could not create failed payment template validation event",
                [
                    'email'           => (string) $memberInfo->email(),
                    'siteId'          => $siteId,
                    'businessGroupId' => $businessGroupId,
                ]
            );
        }
    }
}
