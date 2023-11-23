<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Complete;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchase3DSCompleted;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingParesAndMdException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\Base\Domain\InvalidCommandException;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Services\ManageCreditCardBlacklistTrait;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\CardInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use Throwable;

class CompleteThreeDCommandHandler extends BasePaymentProcessCommandHandler
{
    use ManageCreditCardBlacklistTrait;

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

    /** @var ConfigService */
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
     * CompleteThreeDCommandHandler constructor.
     *
     * @param CompleteThreeDDTOAssembler $assembler              Assembler
     * @param TransactionService         $transactionService     Transaction service
     * @param SessionHandler             $purchaseProcessHandler Purchase process handler
     * @param ConfigService              $configServiceClient    Config service client
     * @param PurchaseService            $purchaseService        Purchase service
     * @param PostbackService            $postbackService        Postback service
     * @param BILoggerService            $biLoggerService        Bi Logger service
     * @param EventIngestionService      $eventIngestionService  Event
     * @param CCForBlackListService      $CCForBlackListService  CC for blacklist service
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
        CCForBlackListService $CCForBlackListService
    ) {
        $this->assembler              = $assembler;
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->biLoggerService        = $biLoggerService;
        $this->transactionService     = $transactionService;
        $this->configServiceClient    = $configServiceClient;
        $this->purchaseService        = $purchaseService;
        $this->postbackService        = $postbackService;
        $this->eventIngestionService  = $eventIngestionService;

        $this->init($CCForBlackListService);
    }

    /**
     * @param Command $command Complete purchase command
     * @return CompleteThreeDHttpDTO
     * @throws InvalidCommandException
     * @throws SessionNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function execute(Command $command)
    {
        if (!$command instanceof CompleteThreeDCommand) {
            throw new InvalidCommandException(CompleteThreeDCommand::class, $command);
        }

        try {
            // retrieve purchase process
            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $command->sessionId());

            if (empty($this->purchaseProcess->redirectUrl())) {
                throw new MissingRedirectUrlException(
                    RestartProcess::create()->toArray()
                );
            }

            if (empty($command->pares()) && empty($command->md())) {
                throw new MissingParesAndMdException(
                    RenderGateway::create()->toArray(),
                    $this->purchaseProcess->redirectUrl()
                );
            }

            if (!$this->purchaseProcess->isThreeDAuthenticated() && !$this->purchaseProcess->isThreeDLookupPerformed()) {
                $redirectUrl = $this->purchaseProcess->redirectUrl();
                $this->clearPurchaseProcess();

                throw new SessionAlreadyProcessedException(
                    (string) $command->sessionId(),
                    RestartProcess::create()->toArray(),
                    $redirectUrl
                );
            }

            $mainPurchase = $this->purchaseProcess->retrieveMainPurchaseItem();
            $site         = $this->configServiceClient->getSite((string) $mainPurchase->siteId());

            if ($site === null) {
                throw new SiteNotExistException();
            }

            $transactionInfo = $this->transactionService->attemptCompleteThreeDTransaction(
                $mainPurchase,
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                $site,
                $this->purchaseProcess->fraudAdvice(),
                $this->purchaseProcess->userInfo(),
                $command->sessionId(),
                $command->pares(),
                $command->md(),
                $this->purchaseProcess->paymentMethod()
            );

            $this->setPaymentInfo($transactionInfo, $this->purchaseProcess->paymentMethod());

            // Call config service for a possible CC blacklist
            $creditCardWasBlacklisted = $this->blacklistCreditCardIfNeeded(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                (string) $transactionInfo->first6(),
                (string) $transactionInfo->last4(),
                (string) $transactionInfo->cardExpirationMonth(),
                (string) $transactionInfo->cardExpirationYear(),
                (string) $this->purchaseProcess->sessionId()
            );

            $this->purchaseProcess->setCreditCardWasBlacklisted($creditCardWasBlacklisted);

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

                // Store the purchase process
                $this->purchaseProcessHandler->update($this->purchaseProcess);
            }
        }
    }

    /**
     * @param Site        $site
     * @param string      $status
     * @param string|null $email
     * @throws Exception
     */
    private function shipFraudPurchase3DSCompletedEvent(Site $site, string $status, ?string $email): void
    {
        if (config('app.feature.event_ingestion_communication.send_3ds_fraud_event')) {
            $fraudPurchase3dsCompleted = new FraudPurchase3DSCompleted(
                $site->id(),
                (string) $site->businessGroupId(),
                $status,
                $email
            );
            $this->eventIngestionService->queue($fraudPurchase3dsCompleted);
        }
    }

    /**
     * @param NewCCTransactionInformation $transactionInfo Transaction info
     * @param string|null                 $paymentMethod   Payment method
     * @return void
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    private function setPaymentInfo(NewCCTransactionInformation $transactionInfo, ?string $paymentMethod): void
    {
        $this->purchaseProcess->setPaymentInfo(
            CardInfo::create(
                $transactionInfo->first6(),
                $transactionInfo->last4(),
                (string) $transactionInfo->cardExpirationMonth(),
                (string) $transactionInfo->cardExpirationYear(),
                $paymentMethod
            )
        );
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
     * The session must be cleared, because on finally block, the session will be updated and this
     * will throw illegal transition state in case process session is valid
     * @return void
     */
    private function clearPurchaseProcess(): void
    {
        $this->purchaseProcess = null;
    }
}
