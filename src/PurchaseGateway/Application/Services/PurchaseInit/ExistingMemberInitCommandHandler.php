<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudRecommendationHelper;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class ExistingMemberInitCommandHandler extends BaseInitCommandHandler
{
    /**
     * @var PaymentTemplateTranslatingService
     */
    private $paymentTemplateService;

    /**
     * @var FraudCsService
     */
    private $fraudServiceCs;

    /**
     * @var RetrieveFraudRecommendationForExistingMemberOnInit
     */
    private $adviceForExistingMemberOnInit;

    /**
     * @var MemberProfileGatewayService
     */
    private $memberProfileGatewayService;

    /**
     * @var PurchaseInitCommandResult
     */
    private $purchaseInitCommandResult;

    /**
     * ExistingMemberInitCommandHandler constructor.
     * @param CascadeService                                     $retrieveCascade               Retrieve Cascade.
     * @param FraudService                                       $fraudService                  Fraud Service.
     * @param NuDataService                                      $nuDataService                 NuData Service.
     * @param PaymentTemplateTranslatingService                  $paymentTemplateService        Payment Template Service
     * @param FraudCsService                                     $fraudServiceCs                Fraud Service Cs
     * @param PurchaseProcessHandler                             $purchaseProcessHandler        User Session Handler
     * @param BILoggerService                                    $biLoggerService               BI Logger Service.
     * @param PurchaseInitDTOAssembler                           $dtoAssembler                  Http Command DTO Assembler.
     * @param BundleValidationService                            $bundleValidationService       Bundle Validation Service.
     * @param RetrieveFraudRecommendationForExistingMemberOnInit $adviceForExistingMemberOnInit Retrieve Fraud Recommendation
     * @param MemberProfileGatewayService                        $memberProfileGatewayService   Member Profile Gateway Service
     * @param PurchaseInitCommandResult                          $purchaseInitCommandResult     Purchase init command result
     * @param EventIngestionService                              $eventIngestionService         Event ingestion service.
     * @param ConfigService                                      $configServiceClient           Config service client.
     */
    public function __construct(
        CascadeService $retrieveCascade,
        FraudService $fraudService,
        NuDataService $nuDataService,
        PaymentTemplateTranslatingService $paymentTemplateService,
        FraudCsService $fraudServiceCs,
        PurchaseProcessHandler $purchaseProcessHandler,
        BILoggerService $biLoggerService,
        PurchaseInitDTOAssembler $dtoAssembler,
        BundleValidationService $bundleValidationService,
        RetrieveFraudRecommendationForExistingMemberOnInit $adviceForExistingMemberOnInit,
        MemberProfileGatewayService $memberProfileGatewayService,
        PurchaseInitCommandResult $purchaseInitCommandResult,
        EventIngestionService $eventIngestionService,
        ConfigService $configServiceClient
    ) {
        parent::__construct(
            $retrieveCascade,
            $fraudService,
            $nuDataService,
            $purchaseProcessHandler,
            $biLoggerService,
            $dtoAssembler,
            $bundleValidationService,
            $eventIngestionService,
            $configServiceClient
        );

        $this->paymentTemplateService        = $paymentTemplateService;
        $this->fraudServiceCs                = $fraudServiceCs;
        $this->adviceForExistingMemberOnInit = $adviceForExistingMemberOnInit;
        $this->memberProfileGatewayService   = $memberProfileGatewayService;
        $this->purchaseInitCommandResult     = $purchaseInitCommandResult;
    }

    /**
     * @param Command $command command
     *
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @throws Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof PurchaseInitCommand) {
            throw new InvalidCommandException(PurchaseInitCommand::class, $command);
        }

        try {
            $this->initPurchaseProcess($command);

            $this->setPaymentTemplates(
                $command->memberId(),
                $command->paymentType(),
                $command->site()->id(),
                $command->initialDays(),
                $command->sessionId()
            );

            if (!empty($this->purchaseProcess->paymentTemplateCollection())) {
                $firstPaymentTemplate = $this->purchaseProcess->paymentTemplateCollection()->firstPaymentTemplate();
                $initialJoinBiller    = $firstPaymentTemplate ? $firstPaymentTemplate->billerName() : null;
            }

            $this->setCascade(
                $command->sessionId(),
                (string) $command->site()->siteId(),
                (string) $command->site()->businessGroupId(),
                $command->clientCountryCode(),
                $command->paymentType(),
                $command->paymentMethod(),
                $command->trafficSource(),
                $command->forceCascade(),
                $initialJoinBiller ?? null
            );

            $this->setFraudAdvice(
                $command,
                $this->purchaseProcess->cascade()->firstBiller()->isThirdParty()
            );

            $this->purchaseProcess->setStateIfThirdParty();

            $this->setNuDataSettings($command);

            $this->shipBiInitializedPurchaseEvent();

            $this->generatePurchaseInitResult($command);

            return $this->dtoAssembler->assemble($this->purchaseInitCommandResult);
        } catch (\Throwable $e) {
            Log::logException($e);
            throw $e;
        } finally {
            // Store the purchase process
            $this->purchaseProcessHandler->create($this->purchaseProcess);
        }
    }

    /**
     * @param string $memberId    Member Id
     * @param string $paymentType Payment type
     * @param string $siteId      Site Id
     * @param int    $initialDays Initial Days
     * @param string $sessionId   Session Id
     *
     * @return void
     */
    protected function setPaymentTemplates(
        string $memberId,
        string $paymentType,
        string $siteId,
        int $initialDays,
        string $sessionId
    ): void {
        /** @var PaymentTemplateCollection $paymentTemplates */
        $paymentTemplates = $this->paymentTemplateService->retrieveAllPaymentTemplates(
            $memberId,
            $paymentType,
            $sessionId
        );

        $this->retrieveFraudAdviceForBins($paymentTemplates, $siteId, $initialDays);

        $this->purchaseProcess->setPaymentTemplateCollection($paymentTemplates);
    }

    /**
     * @param PurchaseInitCommand $command Command
     * @return void
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     */
    protected function generatePurchaseInitResult(PurchaseInitCommand $command): void
    {
        $this->purchaseInitCommandResult->addSessionId((string) $this->purchaseProcess->sessionId());

        $this->purchaseInitCommandResult->addFraudAdvice($this->purchaseProcess->fraudAdvice());
        $this->purchaseInitCommandResult->addFraudRecommendation($this->purchaseProcess->fraudRecommendation());
        $this->purchaseInitCommandResult->addFraudRecommendationCollection($this->purchaseProcess->fraudRecommendationCollection());
        $this->purchaseInitCommandResult->addNuData($this->purchaseProcess->nuDataSettings());
        $this->purchaseInitCommandResult->addPaymentTemplateCollection($this->purchaseProcess->paymentTemplateCollection());
        if ($command->forceCascade()) {
            $this->purchaseInitCommandResult->addForcedBiller(
                (string) $this->purchaseProcess->cascade()->firstBiller()
            );
        }

        $this->purchaseInitCommandResult->addNextAction(
            $this->purchaseProcess->state(),
            $this->purchaseProcess->cascade()->firstBiller(),
            $this->purchaseProcess->fraudAdvice(),
            $this->purchaseProcess->fraudRecommendation()
        );
    }

    /**
     * @param PurchaseInitCommand $command Purchase Init Command
     * @return void
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws IllegalStateTransitionException
     */
    protected function retrieveFraudAdvice(PurchaseInitCommand $command): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.sec_rev')) {
            parent::retrieveFraudAdvice($command);
            return;
        }

        try {
            $email = $this->retrieveMemberEmail(
                $command->memberId(),
                $command->site(),
                $this->purchaseProcess->sessionId(),
                $command->subscriptionId()
            );

            //Purchase process existing credit card needs email as well
            // in a way to avoid retrieve Member profile again we are storing this information
            // on userInfo to be used on sec rev existing credit card.
            if ($email instanceof Email) {
                $this->purchaseProcess->userInfo()->setEmail($email);
            }

            $fraudRecommendationCollection = $this->adviceForExistingMemberOnInit->retrieve(
                $command->site()->businessGroupId(),
                $command->site()->siteId(),
                Ip::create($command->clientIp()),
                CountryCode::create($command->clientCountryCode()),
                $this->purchaseProcess->totalAmount(),
                $this->purchaseProcess->sessionId(),
                $email,
                $command->fraudHeaders()
            );

            $filteredFraudRecommendations = FraudRecommendationHelper::filterFraudRecommendationByPaymentType(
                $fraudRecommendationCollection,
                $command->paymentType()
            );

            $this->purchaseProcess->setFraudRecommendationCollection($filteredFraudRecommendations);
            $this->purchaseProcess->setFraudAdvice(
                FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnInit($filteredFraudRecommendations)
            );

            if ($fraudRecommendationCollection->hasBypassPaymentTemplateValidation()) {
                Log::info('Fraud recommendation suggests to skip bypass template validation');
                $this->purchaseProcess->paymentTemplateCollection()->setAllSafeBins(true);
            }

        } catch (\Exception $exception) {
            Log::logException($exception);
            Log::info('FraudRecommendationDefault Exception throwed. Setting default fraud recommendation');
            FraudRecommendationHelper::setDefaultFraudRecommendationOnInit($this->purchaseProcess);
        }
        $this->detectThreeDUsage();
        $this->purchaseProcess->initStateAccordingToFraudAdvice();
    }

    /**
     * @param string    $memberId       Member Id
     * @param Site      $site           Site
     * @param SessionId $sessionId      Session Id
     * @param string    $subscriptionId Subscription Id
     * @return Email
     * @throws Exception
     */
    private function retrieveMemberEmail(
        string $memberId,
        Site $site,
        SessionId $sessionId,
        ?string $subscriptionId
    ): ?Email {
        try {
            $memberInfo = $this->memberProfileGatewayService->retrieveMemberProfile(
                $memberId,
                $site->id(),
                $site->publicKeys()[0],
                (string) $sessionId,
                $subscriptionId
            );
            return $memberInfo->email();
        } catch (\Exception $e) {
            Log::info('Member information not fetched when retrieving member email from member profile, ' . $e->getMessage() . ', returning empty email');
            return null;
        }
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplates Payment templates
     * @param string                    $siteId           Site Id
     * @param int                       $initialDays      Initial Days
     *
     * @return void
     */
    private function retrieveFraudAdviceForBins(
        PaymentTemplateCollection $paymentTemplates,
        string $siteId,
        int $initialDays
    ): void {
        $paymentTemplateWithCreditCardCollection = new PaymentTemplateCollection();

        foreach ($paymentTemplates as $paymentTemplate) {
            if (!empty($paymentTemplate->firstSix())) {
                $paymentTemplateWithCreditCardCollection->offsetSet(
                    (string) $paymentTemplate->templateId(),
                    $paymentTemplate
                );
            }
        }

        //Note: We are passing call to config service instead of fraud CS service
        if ($paymentTemplateWithCreditCardCollection->count() > 0) {
            // Check if BINs are safe and mark accordingly
            $this->fraudServiceCs->retrieveAdviceFromConfig(
                $paymentTemplateWithCreditCardCollection,
                $siteId,
                $initialDays
            );
        }
    }
}
