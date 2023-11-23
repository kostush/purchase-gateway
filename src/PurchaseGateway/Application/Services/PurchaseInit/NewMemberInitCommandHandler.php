<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudRecommendationHelper;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewMemberOnInit;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class NewMemberInitCommandHandler extends BaseInitCommandHandler
{
    /**
     * @var RetrieveFraudRecommendationForNewMemberOnInit
     */
    private $adviceForNewMemberOnInit;

    /**
     * @var PurchaseInitCommandResult
     */
    private $purchaseInitCommandResult;

    /**
     * NewMemberInitCommandHandler constructor.
     * @param CascadeService                                $retrieveCascade           Retrieve Cascade.
     * @param FraudService                                  $fraudService              Fraud Service.
     * @param NuDataService                                 $nuDataService             NuData Service.
     * @param PurchaseProcessHandler                        $purchaseProcessHandler    User Session Handler
     * @param BILoggerService                               $biLoggerService           BI Logger Service.
     * @param PurchaseInitDTOAssembler                      $dtoAssembler              Http Command DTO Assembler.
     * @param BundleValidationService                       $bundleValidationService   Bundle Validation Service.
     * @param RetrieveFraudRecommendationForNewMemberOnInit $adviceForNewMemberOnInit  Advice
     * @param PurchaseInitCommandResult                     $purchaseInitCommandResult Purchase init command result
     * @param EventIngestionService                         $eventIngestionService     Event ingestion service
     * @param ConfigService                                 $configServiceClient       Config service client.
     */
    public function __construct(
        CascadeService $retrieveCascade,
        FraudService $fraudService,
        NuDataService $nuDataService,
        PurchaseProcessHandler $purchaseProcessHandler,
        BILoggerService $biLoggerService,
        PurchaseInitDTOAssembler $dtoAssembler,
        BundleValidationService $bundleValidationService,
        RetrieveFraudRecommendationForNewMemberOnInit $adviceForNewMemberOnInit,
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
        $this->adviceForNewMemberOnInit  = $adviceForNewMemberOnInit;
        $this->purchaseInitCommandResult = $purchaseInitCommandResult;
    }

    /**
     * @param Command $command command
     *
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @throws \ProBillerNG\Logger\Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof PurchaseInitCommand) {
            throw new InvalidCommandException(PurchaseInitCommand::class, $command);
        }

        try {
            $this->initPurchaseProcess($command);

            $this->setCascade(
                $command->sessionId(),
                (string) $command->site()->siteId(),
                (string) $command->site()->businessGroupId(),
                $command->clientCountryCode(),
                $command->paymentType(),
                $command->paymentMethod(),
                $command->trafficSource(),
                $command->forceCascade()
            );

            $this->setFraudAdvice(
                $command,
                $this->purchaseProcess->cascade()->firstBiller()->isThirdParty()
            );

            $this->purchaseProcess->filterBillersIfThreeDSAdvised();

            $this->purchaseProcess->setStateIfThirdParty();

            $this->setNuDataSettings($command);

            $this->shipBiInitializedPurchaseEvent();

            $this->generatePurchaseInitResult();

            return $this->dtoAssembler->assemble($this->purchaseInitCommandResult);
        } catch (Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::logException($e);
            throw $e;
        } finally {
            // Store the purchase process
            $this->purchaseProcessHandler->create($this->purchaseProcess);
        }
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException
     */
    protected function generatePurchaseInitResult(): void
    {
        $this->purchaseInitCommandResult->addSessionId((string) $this->purchaseProcess->sessionId());

        $this->purchaseInitCommandResult->addFraudAdvice($this->purchaseProcess->fraudAdvice());
        $this->purchaseInitCommandResult->addFraudRecommendation($this->purchaseProcess->fraudRecommendation());
        $this->purchaseInitCommandResult->addFraudRecommendationCollection($this->purchaseProcess->fraudRecommendationCollection());
        $this->purchaseInitCommandResult->addNuData($this->purchaseProcess->nuDataSettings());

        $this->purchaseInitCommandResult->addNextAction(
            $this->purchaseProcess->state(),
            $this->purchaseProcess->cascade()->firstBiller(),
            $this->purchaseProcess->fraudAdvice(),
            $this->purchaseProcess->fraudRecommendation()
        );
    }

    /**
     * @param PurchaseInitCommand $command PurchaseInitCommand
     * @return void
     */
    protected function retrieveFraudAdvice(PurchaseInitCommand $command): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            parent::retrieveFraudAdvice($command);
            return;
        }
        try {
            $fraudRecommendationCollection = $this->adviceForNewMemberOnInit->retrieve(
                $command->site()->businessGroupId(),
                $command->site()->siteId(),
                Ip::create($command->clientIp()),
                CountryCode::create($command->clientCountryCode()),
                SessionId::createFromString((string) $this->purchaseProcess->sessionId()),
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
        } catch (\Exception $exception) {
            Log::logException($exception);
            Log::info('FraudRecommendationDefault Exception throwed. Setting default fraud recommendation');
            FraudRecommendationHelper::setDefaultFraudRecommendationOnInit($this->purchaseProcess);
        }
        $this->detectThreeDUsage();
        $this->purchaseProcess->initStateAccordingToFraudAdvice();
    }
}
