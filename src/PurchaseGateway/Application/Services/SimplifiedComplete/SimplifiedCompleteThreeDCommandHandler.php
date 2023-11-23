<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchase3DSCompleted;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingMandatoryQueryParamsForCompleteException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\CardInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;

class SimplifiedCompleteThreeDCommandHandler extends BasePaymentProcessCommandHandler
{
    /**
     * @var CompleteThreeDDTOAssembler
     */
    private $assembler;

    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var ConfigService
     */
    private $configServiceClient;

    /**
     * @var PurchaseService
     */
    protected $purchaseService;

    /**
     * @var PostbackService
     */
    protected $postbackService;

    /**
     * @var EventIngestionService
     */
    protected $eventIngestionService;

    /**
     * @var BILoggerService
     */
    protected $biLoggerService;

    /**
     * @var SessionHandler
     */
    protected $purchaseProcessHandler;

    /**
     * @var InMemoryRepository
     */
    private $redisRepository;

    /**
     * SimplifiedCompleteThreeDQueryHandler constructor.
     *
     * @param CompleteThreeDDTOAssembler $assembler              Assembler
     * @param TransactionService         $transactionService     Transaction service
     * @param SessionHandler             $purchaseProcessHandler Purchase process handler
     * @param ConfigService              $configServiceClient    Config service client
     * @param PurchaseService            $purchaseService        Purchase service
     * @param PostbackService            $postbackService        Postback service
     * @param BILoggerService            $biLoggerService        Bi Logger service
     * @param EventIngestionService      $eventIngestionService  Event
     * @param InMemoryRepository         $redisRepository        Redis repository.
     */
    public function __construct(
        CompleteThreeDDTOAssembler $assembler,
        TransactionService $transactionService,
        SessionHandler $purchaseProcessHandler,
        ConfigService $configServiceClient,
        PurchaseService $purchaseService,
        PostbackService $postbackService,
        BILoggerService $biLoggerService,
        EventIngestionService $eventIngestionService,
        InMemoryRepository $redisRepository
    ) {
        $this->assembler              = $assembler;
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->biLoggerService        = $biLoggerService;
        $this->transactionService     = $transactionService;
        $this->configServiceClient    = $configServiceClient;
        $this->purchaseService        = $purchaseService;
        $this->postbackService        = $postbackService;
        $this->eventIngestionService  = $eventIngestionService;
        $this->redisRepository        = $redisRepository;
    }

    /**
     * @param Command $command Complete purchase query
     * @return CompleteThreeDHttpDTO
     * @throws IllegalStateTransitionException
     * @throws InvalidCommandException
     * @throws LoggerException
     * @throws SessionNotFoundException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof SimplifiedCompleteThreeDCommand) {
            throw new InvalidCommandException(SimplifiedCompleteThreeDCommand::class, $command);
        }

        try {
            // retrieve purchase process
            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $command->sessionId());

            // validate the redirect URL
            if (empty($this->purchaseProcess->redirectUrl())) {
                throw new MissingRedirectUrlException(
                    RestartProcess::create()->toArray()
                );
            }

            // validate the query string
            if (empty($command->invoiceId()) || empty($command->hash())) {
                throw new MissingMandatoryQueryParamsForCompleteException(
                    RenderGateway::create()->toArray(),
                    $this->purchaseProcess->redirectUrl()
                );
            }

            $mainPurchase = $this->purchaseProcess->retrieveMainPurchaseItem();
            $site         = $this->configServiceClient->getSite((string) $mainPurchase->siteId());

            if ($site === null) {
                throw new SiteNotExistException();
            }

            // If the purchase was already processed or validated by a concurrent call, return the dto without
            // calling transaction service again (first call already called transaction service)
            if ($this->concurrentCallWasMade()) {
                return $this->assembler->assemble($this->purchaseProcess, $site);
            }

            $this->purchaseProcess->authenticateThreeD();

            $transactionInfo = $this->transactionService->attemptSimplifiedCompleteThreeDTransaction(
                $mainPurchase,
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                $site,
                $this->purchaseProcess->fraudAdvice(),
                $this->purchaseProcess->userInfo(),
                $command->sessionId(),
                $command->queryString(),
                $this->purchaseProcess->paymentMethod()
            );

            $this->setPaymentInfo($transactionInfo, $this->purchaseProcess->paymentMethod());

            $this->purchaseProcess->postProcessing();

            $this->purchase = $this->purchaseService->createPurchaseEntity(
                $this->purchaseProcess,
                $site
            );

            $dto = $this->assembler->assemble($this->purchaseProcess, $site);

            if ($this->purchaseProcess->isProcessed()) {
                $this->postbackService->queue($this->buildDtoPostback($dto), $this->getPostbackUrl($site));
            }

            $this->shipFraudPurchase3DSCompletedEvent(
                $site,
                $transactionInfo->status(),
                (string) $this->purchaseProcess->userInfo()->email()
            );

            $this->shipBiProcessedPurchaseEvent($site);

            return $dto;
        } catch (InitPurchaseInfoNotFoundException $ex) {
            throw new SessionNotFoundException($ex);
        } catch (\Exception $ex) {
            Log::logException($ex);
            throw $ex;
        } finally {
            if ($this->purchaseProcess !== null) {
                $this->purchaseProcess->incrementGatewaySubmitNumberIfValid();

                // store purchase status and gateway submit number in redis for concurrent calls handling
                $this->redisRepository->storePurchaseStatus(
                    (string) $this->purchaseProcess->sessionId(),
                    $this->purchaseProcess->state()::name()
                );

                $this->redisRepository->storeGatewaySubmitNumber(
                    (string) $this->purchaseProcess->sessionId(),
                    $this->purchaseProcess->gatewaySubmitNumber()
                );

                // Store the purchase process
                $this->purchaseProcessHandler->update($this->purchaseProcess);
            }
        }
    }

    /**
     * @param Site        $site   Site
     * @param string      $status Status
     * @param string|null $email  Email
     * @return void
     * @throws Exception
     */
    private function shipFraudPurchase3DSCompletedEvent(Site $site, string $status, ?string $email): void
    {
        if (!config('app.feature.event_ingestion_communication.send_3ds_fraud_event')) {
            return;
        }

        $fraudPurchase3dsCompleted = new FraudPurchase3DSCompleted(
            $site->id(),
            (string) $site->businessGroupId(),
            $status,
            $email
        );

        $this->eventIngestionService->queue($fraudPurchase3dsCompleted);
    }

    /**
     * @param CCTransactionInformation $transactionInfo Transaction info
     * @param string|null              $paymentMethod   Payment method
     * @return void
     * @throws LoggerException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    private function setPaymentInfo(CCTransactionInformation $transactionInfo, ?string $paymentMethod): void
    {
        if (!$transactionInfo instanceof NewCCTransactionInformation) {
            return;
        }

        $paymentInfo = CardInfo::create(
            $transactionInfo->first6(),
            $transactionInfo->last4(),
            (string) $transactionInfo->cardExpirationMonth(),
            (string) $transactionInfo->cardExpirationYear(),
            $paymentMethod
        );

        $this->purchaseProcess->setPaymentInfo($paymentInfo);
    }

    /**
     * @return BaseEvent
     * @throws \Exception
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        return PurchaseBiEventFactory::createForNewCC($this->purchaseProcess);
    }

    /**
     * @return bool
     */
    private function concurrentCallWasMade(): bool
    {
        $gatewaySubmitNumberInRedis = $this->redisRepository->retrieveGatewaySubmitNumber(
            (string) $this->purchaseProcess->sessionId()
        );

        if ($this->purchaseProcess->isProcessed()
            || ($this->purchaseProcess->isValid() && $this->purchaseProcess->gatewaySubmitNumber() == $gatewaySubmitNumberInRedis)
        ) {
            return true;
        }

        return false;
    }
}
