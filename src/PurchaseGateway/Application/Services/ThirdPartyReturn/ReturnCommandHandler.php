<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn;

use Exception;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\Base\Domain\InvalidCommandException;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidPayloadException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\UserInfoService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException as TransactionAlreadyProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\FailedDependencyException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\MalformedPayloadException;

class ReturnCommandHandler extends BasePaymentProcessCommandHandler
{
    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var BILoggerService
     */
    protected $biLoggerService;

    /**
     * @var ReturnDTOAssembler
     */
    protected $assembler;

    /**
     * @var SessionHandler
     */
    protected $purchaseProcessHandler;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var PurchaseService
     */
    protected $purchaseService;

    /**
     * @var UserInfoService
     */
    protected $userInfoService;

    /**
     * @var Purchase
     */
    protected $purchase;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * @var PostbackService
     */
    protected $postbackService;

    /**
     * ReturnCommandHandler constructor.
     * @param ReturnDTOAssembler     $assembler              Assembler
     * @param SessionHandler         $purchaseProcessHandler Session handler
     * @param TransactionService     $transactionService     Transaction service
     * @param PurchaseService        $purchaseService        Purchase service
     * @param ConfigService          $configServiceClient    Config Service
     * @param PostbackService        $postbackService        Postback service
     * @param BILoggerService        $biLoggerService        Bi logger
     * @param UserInfoService        $userInfoService        User info service
     * @param EventIngestionService  $eventIngestionService  Event service
     */
    public function __construct(
        ReturnDTOAssembler $assembler,
        SessionHandler $purchaseProcessHandler,
        TransactionService $transactionService,
        PurchaseService $purchaseService,
        ConfigService $configServiceClient,
        PostbackService $postbackService,
        BILoggerService $biLoggerService,
        UserInfoService $userInfoService,
        EventIngestionService $eventIngestionService
    ) {
        $this->assembler              = $assembler;
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->transactionService     = $transactionService;
        $this->purchaseService        = $purchaseService;
        $this->configServiceClient    = $configServiceClient;
        $this->postbackService        = $postbackService;
        $this->biLoggerService        = $biLoggerService;
        $this->userInfoService        = $userInfoService;
        $this->eventIngestionService  = $eventIngestionService;
    }

    /**
     * Executes a command
     *
     * @param Command $command Command
     * @return mixed
     * @throws InvalidCommandException
     * @throws SessionNotFoundException
     * @throws Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof ReturnCommand) {
            throw new InvalidCommandException(ReturnCommand::class, $command);
        }

        try {
            // retrieve purchase process
            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $command->sessionId());

            $this->validatePurchaseProcess((string) $command->sessionId(), $command->transactionId());

            $transaction = $this->transactionService->getTransactionDataBy(
                $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
                $command->sessionId()
            );

            // transaction wasn't already processed by postback
            if ($transaction->transactionInformation()->status() === Transaction::STATUS_PENDING) {
                $billerInteraction = $this->transactionService->addBillerInteraction(
                    TransactionId::createFromString($transaction->transactionInformation()->transactionId()),
                    $transaction->billerName(),
                    $command->sessionId(),
                    $command->payload()
                );

                // update purchase process
                $this->purchaseProcess->returnFromThirdPartyUpdates(
                    $billerInteraction->transactionId(),
                    $billerInteraction->status(),
                    $billerInteraction->paymentType(),
                    $billerInteraction->paymentMethod()
                );

                // user info service
                $this->userInfoService->update($this->purchaseProcess, $command->payload());

                // update session status
                $this->purchaseProcess->postProcessing();

                // create and persist purchase entity
                $this->purchase = $this->purchaseService->createPurchaseEntityForThirdParty($this->purchaseProcess);

                // Ship BI event
                $site = $this->configServiceClient->getSite(
                    (string) $this->purchaseProcess->retrieveMainPurchaseItem()->siteId()
                );
                $this->shipBiProcessedPurchaseEvent($site);
            } else {
                $this->purchaseService->restorePurchaseInSession($this->purchaseProcess);
                $this->purchaseProcess->updateTransactionStateFor(
                    $transaction->transactionInformation()->transactionId(),
                    $transaction->transactionInformation()->status()
                );

                // the session status needs to be updated in case postback
                // was faster than return and it failed
                $this->purchaseProcess->postProcessing();
            }

            $site = $this->configServiceClient->getSite(
                (string) $this->purchaseProcess->retrieveMainPurchaseItem()->siteId()
            );

            $dto = $this->assembler->assemble($this->purchaseProcess, $site);

            if ($this->purchaseProcess->isProcessed()) {
                $this->postbackService->queue(
                    $this->buildDtoPostback($dto, $this->purchaseProcess->userInfo()),
                    $this->getPostbackUrl($site)
                );
            }

            return $dto;
        } catch (InitPurchaseInfoNotFoundException $ex) {
            throw new SessionNotFoundException($ex);
        } catch (TransactionAlreadyProcessedException $ex) {
            $redirectUrl = $this->purchaseProcess->redirectUrl();
            throw new TransactionAlreadyProcessed(
                RestartProcess::create()->toArray(),
                $redirectUrl ?? ""
            );
        } catch (MalformedPayloadException $exception) {
            throw new FailedDependencyException($exception->serviceName);
        } catch (Exception $ex) {
            Log::logException($ex);
            throw $ex;
        } finally {
            if (isset($this->purchaseProcess)) {
                $this->purchaseProcess->incrementGatewaySubmitNumberIfValid();

                // update state to pending for declined transaction scenario
                $this->purchaseProcess->updateStateForFailedReturnFlow();

                $this->purchaseProcessHandler->update($this->purchaseProcess);
            }
        }
    }

    /**
     * @param string $sessionId     Session id
     * @param string $transactionId Transaction id
     * @return void
     * @throws MissingRedirectUrlException
     * @throws SessionAlreadyProcessedException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidPayloadException
     */
    protected function validatePurchaseProcess(string $sessionId, string $transactionId): void
    {
        // check if session is already processed
        if (!($this->purchaseProcess->isRedirected())) {
            $returnUrl = $this->purchaseProcess->redirectUrl();
            $this->clearProcessSession();
            throw new SessionAlreadyProcessedException(
                $sessionId,
                RestartProcess::create()->toArray(),
                $returnUrl
            );
        }

        if (empty($this->purchaseProcess->redirectUrl())) {
            throw new MissingRedirectUrlException(
                RestartProcess::create()->toArray()
            );
        }

        // check if transaction id from received payload exists for main purchase or cross sales
        if (!$this->purchaseProcess->checkIfTransactionIdExist($transactionId)) {
            throw new InvalidPayloadException(
                $sessionId,
                RestartProcess::create()->toArray(),
                $this->purchaseProcess->redirectUrl()
            );
        }
    }

    /**
     * @return void
     */
    private function clearProcessSession(): void
    {
        $this->purchaseProcess = null;
    }

    /**
     * @param ProcessPurchaseGeneralHttpDTO $dto      Process Purchase General Http DTO
     * @param UserInfo|null                 $userInfo User info
     * @return PostbackResponseDto
     */
    protected function buildDtoPostback(
        ProcessPurchaseGeneralHttpDTO $dto,
        ?UserInfo $userInfo = null
    ): PostbackResponseDto {
        return PostbackResponseDto::createFromResponseData(
            $dto,
            $dto->tokenGenerator(),
            $this->purchaseProcess->publicKeyIndex(),
            $this->purchaseProcess->sessionId(),
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveProcessedCrossSales(),
            $userInfo
        );
    }

    /**
     * @return BaseEvent
     * @throws Exception
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        return PurchaseBiEventFactory::createForNewCC($this->purchaseProcess);
    }
}
